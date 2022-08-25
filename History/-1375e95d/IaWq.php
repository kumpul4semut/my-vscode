<?php defined('BASEPATH') OR exit('No direct script access allowed');
        
use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Api extends RestController {

	const KEY = 'C0DEIGNITER_KURM4M3D!4'; //jwt key untuk otentifikasi billing

	protected $user_logged_in;

	// ------------------------------------------------------------------------

	const KEY_BMI = 'KeyBmi@2022kurmam3dia'; // jwt key on BMI
	const USERNAME = 'admin@admin.com';
	const PASSWORD = 'password';

	// ------------------------------------------------------------------------
	
	/**
	 * __construct
	 *
	 * controller construct h2h BMI
	 * 
	 * @param none
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('M_api', 'api');
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * jwt_encode
	 *
	 * @param  array $payload
	 * @return void
	 */
	protected function jwt_encode(array $payload)
	{
		return JWT::encode($payload, self::KEY, 'HS256');
	}

	// ------------------------------------------------------------------------
	
	/**
	 * jwt_decode
	 *
	 * @param  string $token
	 * @return void
	 */
	protected function jwt_decode(string $token)
	{
		return JWT::decode($token, new Key(self::KEY, 'HS256'));
	}

	// ------------------------------------------------------------------------
	
	/**
	 * create_token
	 *
	 * @param  array $user
	 * @return array
	 */
	private function _create_token(array $user)
	{
		try 
		{
			$date = new DateTime();

			$token['user'] = $user;
			$token['iat'] = $date->getTimestamp();
			$token['exp'] = $date->getTimestamp() + 60 * 60 * 24 * 360; //expired 360 days

			$output['token'] = $this->jwt_encode($token);
			$decoded = $this->jwt_decode($output['token']);
			$output['iat'] = date('Y-m-d h:i:s', $decoded->iat);
			$output['exp'] = date('Y-m-d h:i:s', $decoded->exp);

			return $output;
		} 
		catch (Exception $e) 
		{
			return $e->getMessage();
		}
	}

	// ------------------------------------------------------------------------
	
	/**
	 * authorized
	 *
	 * @return void
	 */
	public function authorized()
	{
		try 
		{
			$headers = $this->input->get_request_header('Authorization');
			$token = '';
			if (!empty($headers)) 
			{
				if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) 
				{
					$token = !empty($matches[1]) ? $matches[1] : null;
				}
			}
            $this->user_logged_in = $this->jwt_decode($token)->user;
        } 
		catch (Exception $e) 
		{
            $this->response([
                'status'  => false,
                'message' =>  $e->getMessage(),
				'error_code' => 401,
                'data'   => []
            ], 401);
        }
	}

	// ------------------------------------------------------------------------

	/**
	 * 
	 * register_post
	 *
	 * @return void
	 */
	public function register_post()
	{
		$rules = [
			[
				'field' => 'fullname',
				'label' => 'Nama Lengkap',
				'rules' => 'trim|required|alpha_numeric_spaces'
			],
			[
				'field' => 'email',
				'label' => 'Email',
				'rules' => 'trim|required|valid_email|is_unique[users.email]'
			],
			[
				'field' => 'phone',
				'label' => 'No. Telp',
				'rules' => 'trim|required|numeric|min_length[11]'
			],
			[
				'field' => 'password',
				'label' => 'Password',
				'rules' => 'trim|required'
			],
			[
				'field' => 'password_confirm',
				'label' => 'Password Confirm',
				'rules' => 'trim|required|matches[password]'
			],

		];

		$this->form_validation->set_rules($rules);
		
		if ($this->form_validation->run() == FALSE) 
		{
			$response = array(
				'status'     => false,
				'message'    => $this->form_validation->error_array(),
				'data'       => array()				
			);
			return $this->response($response, 400);
		}

		$additional_data = array(
			'first_name' 	=> $this->input->post('fullname'), // nama lengkap
			'phone' 		=> $this->input->post('phone'),
		);

		$register = $this->ion_auth->register($this->input->post('email'), $this->input->post('password'), strtolower($this->input->post('email')), $additional_data);			

		if($register == FALSE)
		{
			$response = [
				'status'     => false,
				'message'    => strip_tags($this->ion_auth->errors()),
				'error_code' => 400,
				'data'       => array()
			];

			return $this->response($response, 400);
		}
		else
		{
			$user = $this->db->get_where('users', array('email' => $this->input->post('email')))->row();

			$user_token = array(
				'id' 	=> $user->id,
				'email' => $user->email,
			);
			$token = $this->_create_token($user_token);

			$response = array(
				'status'  => true,
				'message' => 'User registered successfully!',
				'data'    => $token
			);
			return $this->response($response, 200);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * 
	 * Login post
	 *
	 * @return void
	 */
	public function login_post()
	{
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		$this->form_validation->set_rules('password', 'Password', 'trim|required');
		
		if ($this->form_validation->run() == FALSE) 
		{
			$response = array(
				'message'	=> 'Email and password required !',
				'errors'	=> $this->form_validation->error_array()
			);
			return $this->response($response, 400);
		}

		$get_email 		= $this->input->post('email');
		$get_password 	= $this->input->post('password');

		if ($this->ion_auth->login($get_email, $get_password, TRUE)) 
		{
			$user = $this->db->get_where('users', array('email' => $get_email))->row();	

			$user_token = array(
				'id' 	=> $user->id,
				'email' => $user->email,
			);

			$token = $this->_create_token($user_token);

			$response = array(
				'status'  => true,
				'message' => strip_tags($this->ion_auth->messages()),
				'data'    => $token
			);

			return $this->response($response, 200);
		} 
		else 
		{
			$response = array(
				'status'     => false,
				'message'    => strip_tags($this->ion_auth->errors()),
				'data'       => array()
			);
			return $this->response($response, 400);
		}

	}

	// ------------------------------------------------------------------------
	
	/**
	 * create_billing_post
	 *
	 * controller untuk membuat billing virtual account 
	 * 
	 * @return json
	 */
	public function create_billing_post()
	{
		try 
		{
			$this->authorized();

			$this->form_validation->set_rules('trx_id', 'Trx Id', 'trim|callback_check_trx_id');
			$this->form_validation->set_rules('trx_amount', 'Trx Amount', 'trim|numeric|required|callback_check_trx_amount');
			$this->form_validation->set_rules('billing_type', 'Billing Type', 'trim|required|alpha');
			$this->form_validation->set_rules('virtual_account', 'Virtual Account', 'trim|required|max_length[10]|min_length[10]');
			$this->form_validation->set_rules('customer_name', 'Customer Name', 'trim|required|callback_alpha_dash_space|max_length[30]');
			$this->form_validation->set_rules('customer_email', 'Customer Email', 'trim|required');
			$this->form_validation->set_rules('customer_phone', 'Customer Phone', 'trim|required');
	
			// validasi form
			if ($this->form_validation->run() == FALSE) 
			{
				$response = array(
                    'message'    => 'Billing failed created!',
					'data'       =>  $this->form_validation->error_array()
                );
                throw new Exception(json_encode($response));					
			}

			// insert billing
			$insert = $this->api->create_billing($this->user_logged_in);

			if(!$insert)
			{
				$response = array(
                    'message'    => 'Billing failed created!',
					'data'       =>  @$this->api->messages ? $this->api->messages : $this->db->error()
                );
                throw new Exception(json_encode($response));
			}	
				
			$response = array(
				'status'  => true,
				'message' => 'Billing created successfully!',
				'data'    => $this->api->messages,
			);

			// insert log create billing
			$insert_log = array(
				'trx_id'             => $this->api->messages['trx_id'],
				'request'            => json_encode($_POST),
				'request_timestamp'  => date('Y-m-d H:i:s'),
				'response'           => json_encode($response),
				'response_timestamp' => date('Y-m-d H:i:s'),
			);
			$this->db->insert('log_billing', $insert_log);

			return $this->response($response, 200);
		} 
		catch (\Exception $e) 
		{

			$exception    = json_decode($e->getMessage());

			$response = array(
				'status'  => false,
				'message' => @$exception->message ? $exception->message : $e->getMessage(),
				'data'    => @$exception->data ? $exception->data : array()
			);

            return  $this->response($response, 400);
		}	

	}

	// ------------------------------------------------------------------------
	
	/**
	 * alpha_dash_space
	 *
	 * fungsi form validation just alpha adn space
	 * 
	 * @param  string $str
	 */
	function alpha_dash_space($str)
	{
		if( ! preg_match("/^([-a-z_ ])+$/i", $str))
		{
			$this->form_validation->set_message(  __FUNCTION__ , 'The %s only alphabet and space character!');
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	} 

	// ------------------------------------------------------------------------		
	
	/**
	 * check_trx_id
	 *
	 * fungsi form validasi trx id billing
	 * 
	 * @param none
	 */
	public function check_trx_id()
	{
		$trx_id = $this->input->post('trx_id');

		// trx_id di check uniqe hanya jika diisi
		if($trx_id)
		{
			$result = $this->api->is_unique_trx_id($trx_id);

			if($result == 0)
			{
				$response = TRUE;
			}
			else 
			{
				$this->form_validation->set_message(  __FUNCTION__ , 'The %s transaksi ID already exist');
				$response = FALSE;
			}
		}
		else
		{
			$response = TRUE;
		}
		return $response;									

	}

	// ------------------------------------------------------------------------
	
	/**
	 * check_trx_amount
	 *
	 * fungsi form validation trx amount billing
	 * 
	 * @param none
	 */
	public function check_trx_amount()
	{
		$trx_amount = $this->input->post('trx_amount');
		$minimal = 10000;

		// validasi minimal amount 10.000 
		if(trim(intval($trx_amount)) < $minimal)
		{
			$this->form_validation->set_message(  __FUNCTION__ , 'The %s minimal '.$minimal.'');
			$response = FALSE;
		}
		else 
		{
			$response = TRUE;
		}

		return $response;									

	}

	// ------------------------------------------------------------------------
	
	/**
	 * jwt_encode_bmi
	 *
	 * fungsi encode jwt untuk BMI
	 * 
	 * @param  array $payload
	 * @return string
	 */
	public function jwt_encode_bmi(array $payload)
	{
		return JWT::encode($payload, self::KEY_BMI, 'HS256');
	}

	// ------------------------------------------------------------------------
	
	/**
	 * jwt_decode_bmi
	 *
	 * fungsi decode jwt untuk BMI
	 * 
	 * @param  string $token
	 * @return object
	 */
	public function jwt_decode_bmi(string $token)
	{
		return JWT::decode($token, new Key(self::KEY_BMI, 'HS256'));
	}

	// ------------------------------------------------------------------------
	
	/**
	 * bmi_post
	 *
	 * controller handle request dari BMI
	 * 
	 * @return jwt
	 */
	public function bmi_post()
	{
		try 
		{
			// decrypt jwt 
			$request =  file_get_contents( 'php://input' );
			$decode = $this->jwt_decode_bmi($request);

			// check method
			switch ($decode->METHOD) 
			{
				case 'SIGNON':
					$result = $this->api->handle_signon($decode);

					// save to log
					$this->db->insert('log_sign', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;
					
				case 'SIGNOFF':
					$result = $this->api->handle_signoff($decode);

					// save to log
					$this->db->insert('log_sign', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;

				case 'ECHO':
					$result = $this->api->handle_echo($decode);

					// save to log
					$this->db->insert('log_echo', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;

				case 'INQUIRY':
					$result = $this->api->handle_inquiry($decode);

					// save to log
					$this->db->insert('log_inquiry', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;

				case 'PAYMENT':
					$result = $this->api->handle_payment($decode);

					// save to log
					$this->db->insert('log_payment', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;

				case 'REVERSAL':
					$result = $this->api->handle_reversal($decode);

					// save to log
					$this->db->insert('log_reversal', [
						'request'            => json_encode($decode),
						'response'           => json_encode($result),
						'request_timestamp'  => date('Y-m-d H:i:s', time()),
						'response_timestamp' => date('Y-m-d H:i:s', time()),
					]);

					// print respon for BMI
					echo $this->jwt_encode_bmi($result);

					break;
				
				default:
					echo $this->jwt_encode_bmi(['your method can not be handle!']);
					break;
			}

        } 
		catch (Exception $e) 
		{
			echo $this->jwt_encode_bmi([
                'status'     => false,
                'message'    => $e->getMessage(),
                'error_code' => 401,
                'data'       => []
            ]);
        }

	}

	// ------------------------------------------------------------------------
		
	/**
	 * create_example_jwt
	 *
	 * fungsi bantuan untuk membuat contoh jwt dari BMI
	 * 
	 * @return string
	 */
	public function create_example_jwt_get()
	{
		// sign on 
		// $config = [
		// 	'SIGNONINFO' => time().';bankmuamalatindonesia',
		// 	'METHOD'=>'SIGNON',
		// 	'USERNAME'=> self::USERNAME,
		// 	'PASSWORD'=> self::PASSWORD,
		// ];

		// ------------------------------------------------------------------------
		
		// sign off
		// $config = [
		// 	'SIGNONINFO' => time().';bankmuamalatindonesia',
		// 	'METHOD'=>'SIGNOFF',
		// 	'USERNAME'=> self::USERNAME,
		// 	'PASSWORD'=> self::PASSWORD,
		// ];

		// ------------------------------------------------------------------------

		// echo
		// $config = [
		// 	"ECHODATE" => date('YYYYMMDDHHmmSS', time()),
		// 	"METHOD"   => 'ECHO',
		// 	"USERNAME" => self::USERNAME,
		// 	"PASSWORD" => self::PASSWORD
		// ];

		// ------------------------------------------------------------------------

		// inquiry
		// $config = [
		// 	"CCY"       => "360",
		// 	"VANO"      => "4444001122334455",
		// 	"TRXDATE"   => date('Ymdhis', time()),
		// 	"METHOD"    => "INQUIRY",
		// 	"USERNAME"  => self::USERNAME,
		// 	"PASSWORD"  => self::PASSWORD,
		// 	"CHANNELID" => 1,
		// 	"REFNO"     => "220819101816"
		// ];

		// ------------------------------------------------------------------------

		// payment
		$config = [
			"TRXDATE"   => date('Ymdhis', time()),
			"CCY"       => "360",
			"REFNO"     => "220819101816",
			"BILL"      => "1000000",
			"PAYMENT"   => "1000000",
			"CHANNELID" => "1",
			"METHOD"    => "PAYMENT",
			"VANO"      => "4444001122334455",
			"CUSTNAME"  => "jujun jamaludin",
			"USERNAME"  => self::USERNAME,
			"PASSWORD"  => self::PASSWORD,
		];

		// ------------------------------------------------------------------------

		// $config = [
		// 	"CCY"       => "360",
		// 	"CUSTNAME"  => "jujun jamaludin",
		// 	"TRXDATE"   => date('Ymdhis', time()),
		// 	"REFNO"     => "220819101816",
		// 	"BILL"      => "1000000",
		// 	"CHANNELID" => "1",
		// 	"METHOD"    => "REVERSAL",
		// 	"PYMTDATE"  => date('Ymdhis', time()),
		// 	"PAYMENT"   => "1000000",
		// 	"VANO"      => "4444001122334455",
		// 	"USERNAME"  => self::USERNAME,
		// 	"PASSWORD"  => self::PASSWORD,
		// ];
		echo $this->jwt_encode_bmi($config);
	}

}

/* End of file Api.php and path \application\controllers\Api.php */
