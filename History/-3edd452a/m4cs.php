<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
                        
class M_api extends CI_Model 
{	
	const PREFIX = 4444;

	const MIDDLE = '00'; // 2 digit kedua adalah kode produk yang ditentukan sendiri oleh pihak mitra

	public $messages = array();

	public $currency = '360';

	public $billing_type = array('C', 'O');

	// ------------------------------------------------------------------------
	
	/**
	 * is_unique_trx_id
	 *
	 * model untuk unique trx id
	 * 
	 * @param  string $trx_id
	 * @return bool
	 */
	public function is_unique_trx_id($trx_id)
	{
		$query = $this->db->where('trx_id', $trx_id)->get('billing')->num_rows();

		return $query;
	}

	// ------------------------------------------------------------------------
		
	/**
	 * set_message
	 *
	 * fungsi model create message model billing
	 * 
	 * @param  mixed $message array | string
	 * @return string
	 */
	public function set_message($message)
	{
		if(is_array($message))
		{
			$this->messages = $message;
		}
		else
		{
			$this->messages[] = $message;
		}

		return $message;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * create_billing
	 *
	 * model create billing
	 * 
	 * @param $user_login
	 * @return bool
	 */
	public function create_billing($user_login) 
	{
		$this->db->trans_start();
		//get post data
		$trx_id          = @$this->input->post('trx_id') ?  $this->input->post('trx_id')  : date('ymdhis');
		$billing_type    = $this->input->post('billing_type');
		$virtual_account = self::PREFIX.SELF::MIDDLE.$this->input->post('virtual_account');
		$bill            = $this->input->post('trx_amount');
		$customer_name   = $this->input->post('customer_name');
		$customer_email  = $this->input->post('customer_email');
		$customer_phone  = $this->input->post('customer_phone');
		$description     = @$this->input->post('description');
		$description2    = @$this->input->post('description2');

		if(in_array($billing_type, $this->billing_type) == FALSE)
		{
			// jika belum expired return unique virtual account
			$this->set_message(array('billing_type' => 'Hanya mendukung tipe billing C dan O'));
			return FALSE;
		}

		// cek billing type close payment
		if($billing_type == 'C')
		{
			$status = 'unpaid';
			$datetime_expired = @$this->input->post('datetime_expired') ? $this->input->post('datetime_expired') : date('Y-m-d H:i:s',strtotime('+2 day'));
		}
		else
		{
			$status = 'aktif';
			// jika open payment datetime expired kosong buat 100 tahun
			$datetime_expired = @$this->input->post('datetime_expired') ? $this->input->post('datetime_expired') : date('Y-m-d H:i:s',strtotime('+100 year'));
		}

		// check sudah ada virtual account
		$check_virtual = $this->db->where(['virtual_account' => $virtual_account, 'status' => 'unpaid'])->get('billing');
		if($check_virtual->num_rows() > 0)
		{
			$virtual  = $check_virtual->row();

			if(time() > strtotime($virtual->datetime_expired))
			{
				// ubah status jadi expired
				$this->db->where('id', $virtual->id)->update('billing', array('status' => 'expired'));
			}
			else
			{
				// jika belum expired return unique virtual account
				$this->set_message(array('virtual_account' => 'Virtual account sudah digunakan!'));
				return FALSE;
			}
		}

		$data_insert = array(
			'id_user'          => $user_login->id,
			'trx_id'           => $trx_id,
			'refno'            => '',
			'bill'             => $bill,
			'billing_type'     => $billing_type,
			'customer_name'    => $customer_name,
			'customer_email'   => $customer_email,
			'customer_phone'   => $customer_phone,
			'virtual_account'  => $virtual_account,
			'datetime_expired' => $datetime_expired,
			'description'      => $description,
			'description2'     => $description2,
			'status'           => $status,
			'currency'         => $this->currency,
			'created_at'       => date('Y-m-d H:i:s', time()),
		);
		$this->db->insert('billing', $data_insert);

		// set message
		$id = $this->db->insert_id();
		$billing = $this->db->where('id', $id)->get('billing')->row();
		$message = array(
			'trx_id'          => $billing->trx_id,
			'virtual_account' => $billing->virtual_account,
		);
		$this->set_message($message);
		$this->db->trans_complete();
		
		return $this->db->trans_status();
	}

	 /**
     * create_billing_users
     *
     * model check create billing di tabel users untuk api create billing
     * 
     * @param  string $no_va
     * @param  string $first_name
     * @param  string $email
     * @param  string $phone
     * @param  string $tipe_billing
     * @return bool
     */
    public function create_billing_users($no_va, $first_name, $email, $phone)
    {
        // check no va sudah terdaftar di tabel users
        $check_no_va = $this->db->get_where('users', array('no_va' => $no_va))->num_rows();

        // jika belum insert ke users dengan random password dengan ion auth create
        if($check_no_va < 1)
        {
            $username = $no_va;
            $password = md5(rand());
            $email = $email;
            $additional_data = array(
                        'first_name'   => $first_name,
                        'phone'        => $phone,
                        'no_va'        => $no_va,
                        );
            $group = array('2'); // set user is user group access
            $respon = $this->ion_auth->register($username, $password, $email, $additional_data, $group);
        }
        else
        {
            // jika sudah update no va users tersebut
            $id = $this->db->get_where('users', array('no_va' => $no_va))->row()->id;
            $data = array(
				'first_name'   => $first_name,
				'phone'        => $phone,
				'no_va'        => $no_va,
				);
            $respon = $this->ion_auth->update($id, $data);
        }

        return $respon;
    }

	// ------------------------------------------------------------------------
	
	/**
	 * handle_signon
	 *
	 * model handle sign on BMI
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_signon($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			// update users status sign BMI
			$id_user = $this->ion_auth->get_user_id();
			$this->ion_auth->update($id_user, ['bmi_status_sign' => true]);

			$response = [
				"ERR" => time().';00;'.$get_email,
				"METHOD"=>"SIGNON" 
			];
		}
		else
		{
			// update users status sign BMI
			$id_user = $this->ion_auth->get_user_id();
			$this->ion_auth->update($id_user, ['bmi_status_sign' => false]);

			$response = [
				"ERR" => time().';12;'.$get_email,
				"METHOD"=>"SIGNON" 
			];
		}

		return $response;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * handle_signoff
	 *
	 * model handle sign on BMI
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_signoff($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			// update users status sign BMI
			$id_user = $this->ion_auth->get_user_id();
			$this->ion_auth->update($id_user, ['bmi_status_sign' => false]);

			$response = [
				"ERR" => time().';00;'.$get_email,
				"METHOD"=>"SIGNOFF" 
			];
		}
		else
		{
			// update users status sign BMI
			$response = [
				"ERR" => time().';12;'.$get_email,
				"METHOD"=>"SIGNOFF" 
			];
		}

		return $response;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * handle_echo
	 *
	 * model handle echo dari bmi
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_echo($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			$response = [
				"ERR"    => "00",
				"METHOD" => "ECHO"
			];
		}
		else
		{
			$response = [
				"ERR"    => '12',
				"METHOD" => "ECHO"
			];
		}

		return $response;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * handle_inquiry
	 *
	 * model handle inquiry dari bmi
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_inquiry($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			// get request
			$virtual_account = $request->VANO;
			// get billing
			$get_billing = $this->db->where(['virtual_account' => $virtual_account])->get('billing');

			if($get_billing->num_rows() > 0)
			{
				if($get_billing->row()->status === 'paid')
				{
					// VANO sudah dibayar
					$response = [
						"CCY"          => $get_billing->row()->currency,
						"BILL"         => intval($get_billing->row()->bill) * 100,
						"DESCRIPTION"  => $get_billing->row()->description,
						"ERR"		   => '88',
						"METHOD"       => "INQUIRY",
						"DESCRIPTION2" => $get_billing->row()->description2,
						"CUSTNAME"     =>  $get_billing->row()->customer_name,
					];
				}
				else
				{
					// update billing
					$this->db->where('id', $get_billing->row()->id)->update('billing', ['refno' => $request->REFNO]);

					// VANO ada dan belum dibayar
					$response = [
						"CCY"          => $get_billing->row()->currency,
						"BILL"         => intval($get_billing->row()->bill) * 100,
						"DESCRIPTION"  => $get_billing->row()->description,
						"ERR"		   => '00',
						"METHOD"       => "INQUIRY",
						"DESCRIPTION2" => $get_billing->row()->description2,
						"CUSTNAME"     =>  $get_billing->row()->customer_name,
					];
				}
			}
			else
			{
				// VANO tidak ditemukan
				$response = [
					"CCY"          => @$get_billing->row()->currency,
					"BILL"         => @intval($get_billing->row()->bill) * 100,
					"DESCRIPTION"  => @$get_billing->row()->description,
					"ERR"		   => '15',
					"METHOD"       => "INQUIRY",
					"DESCRIPTION2" => @$get_billing->row()->description2,
					"CUSTNAME"     => @$get_billing->row()->customer_name,
				];
			}
		}
		else
		{
			// failed autentikasi
			$response = [
				"CCY"          => '',
				"BILL"         => '',
				"DESCRIPTION"  => "autentifikasi failed",
				"ERR"		   => '30',
				"METHOD"       => "INQUIRY",
				"DESCRIPTION2" => "",
				"CUSTNAME"     =>  "",
			];
		}

		return $response;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * handle_payment
	 *
	 * model handle payment dari bmi
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_payment($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			// get request
			$virtual_account = $request->VANO;
			// get billing
			$get_billing = $this->db->where(['virtual_account' => $virtual_account])->get('billing');

			if($get_billing->num_rows() > 0)
			{
				if($get_billing->row()->status === 'paid')
				{
					// VANO sudah dibayar
					$response = [
						"CCY"          => $get_billing->row()->currency,
						"BILL"         => intval($get_billing->row()->bill) * 100,
						"DESCRIPTION"  => $get_billing->row()->description,
						"ERR"		   => '88',
						"METHOD"       => "PAYMENT",
						"DESCRIPTION2" => $get_billing->row()->description2,
						"CUSTNAME"     =>  $get_billing->row()->customer_name,
					];
				}
				else
				{
					// check bill sama dengan payment dengan jenis full
					$bill = (intval($request->PAYMENT) / 100 );
					if($get_billing->row()->bill != $bill)
					{
						$response = [
							"CCY"          => $get_billing->row()->currency,
							"BILL"         => intval($get_billing->row()->bill) * 100,
							"DESCRIPTION"  => $get_billing->row()->description,
							"ERR"		   => '16',
							"METHOD"       => "PAYMENT",
							"DESCRIPTION2" => $get_billing->row()->description2,
							"CUSTNAME"     =>  $get_billing->row()->customer_name,
						];
					}
					else
					{
						// update billing paid
						$this->db->where('id', $get_billing->row()->id)->update('billing', ['status' => 'paid']);

						// insert ke tabel payment
						$payment_insert = array(
							'id_user'          => $this->ion_auth->get_user_id(),
							'trx_id'           => $get_billing->row()->trx_id,
							'refno'            => $request->REFNO,
							'bill'             => (intval($request->BILL) / 100 ),
							'payment'          => (intval($request->PAYMENT) / 100 ),
							'billing_type'     => $get_billing->row()->billing_type,
							'customer_name'    => $request->CUSTNAME,
							'customer_email'   => $get_billing->row()->customer_email,
							'customer_phone'   => $get_billing->row()->customer_phone,
							'virtual_account'  =>  $request->VANO,
							'datetime_expired' => $get_billing->row()->datetime_expired,
							'description'      => $get_billing->row()->description,
							'description2'     => $get_billing->row()->description2,
							'status'           => 'paid',
							'currency'         => $get_billing->row()->currency,
							'created_at'       => date('Y-m-d H:i:s', time()),
						);

						$query_insert = $this->db->insert('payment', $payment_insert);

						if($query_insert)
						{
							$response = [
								"CCY"          => $get_billing->row()->currency,
								"BILL"         => intval($get_billing->row()->bill) * 100,
								"DESCRIPTION"  => $get_billing->row()->description,
								"ERR"		   => '00',
								"METHOD"       => "PAYMENT",
								"DESCRIPTION2" => $get_billing->row()->description2,
								"CUSTNAME"     =>  $get_billing->row()->customer_name,
							];
						}
						else
						{
							$response = [
								"CCY"          => $get_billing->row()->currency,
								"BILL"         => intval($get_billing->row()->bill) * 100,
								"DESCRIPTION"  => $get_billing->row()->description,
								"ERR"		   => '12',
								"METHOD"       => "PAYMENT",
								"DESCRIPTION2" => $get_billing->row()->description2,
								"CUSTNAME"     =>  $get_billing->row()->customer_name,
							];
						}
						
					}
				}
			}
			else
			{
				// VANO tidak ditemukan
				$response = [
					"CCY"          => @$get_billing->row()->currency,
					"BILL"         => @intval($get_billing->row()->bill) * 100,
					"DESCRIPTION"  => @$get_billing->row()->description,
					"ERR"		   => '30',
					"METHOD"       => "PAYMENT",
					"DESCRIPTION2" => @$get_billing->row()->description2,
					"CUSTNAME"     => @$get_billing->row()->customer_name,
				];
			}
		}
		else
		{
			// failed autentikasi
			$response = [
				"CCY"          => '',
				"BILL"         => '',
				"DESCRIPTION"  => "autentifikasi failed",
				"ERR"		   => '30',
				"METHOD"       => "PAYMENT",
				"DESCRIPTION2" => "",
				"CUSTNAME"     =>  "",
			];
		}

		return $response;
	}

	// ------------------------------------------------------------------------
	
	/**
	 * handle_reversal
	 *
	 * model handle revfersal atau menggagalkan billing dari bmi
	 * 
	 * @param object $request
	 * @return array
	 */
	public function handle_reversal($request)
	{
		$get_email = $request->USERNAME;
		$get_password = $request->PASSWORD;

		if ($this->ion_auth->login($get_email, $get_password, TRUE))
		{
			// get request
			$virtual_account = $request->VANO;
			// get billing
			$get_billing = $this->db->where(['virtual_account' => $virtual_account])->get('billing');

			if($get_billing->num_rows() > 0)
			{
				if($get_billing->row()->status === 'gagal')
				{
					// reversal sudah dilakukan
					$response = [
						"ERR"		   => '30',
						"METHOD"       => "REVERSAL",
					];
				}
				else
				{
					// update billing jadi gagal
					$query_reversal = $this->db->where('id', $get_billing->row()->id)->update('billing', ['status' => 'gagal']);

					if($query_reversal)
					{
						// reversal berhasil
						$response = [
							"ERR"		   => '00',
							"METHOD"       => "REVERSAL",
						];
					}
					else
					{
						// reversal gagal
						$response = [
							"ERR"		   => '30',
							"METHOD"       => "REVERSAL",
						];
					}
				}
			}
			else
			{
				// VANO tidak ditemukan
				$response = [
					"ERR"		   => '30',
					"METHOD"       => "REVERSAL",
				];
			}
		}
		else
		{
			// failed autentikasi
			$response = [
				"ERR"		   => '30',
				"METHOD"       => "REVERSAL",
			];
		}

		return $response;
	}

}


/* End of file M_api.php and path \application\models\M_api.php */
