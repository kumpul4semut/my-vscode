<?php defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class Kontak extends ADMIN_Controller
{
	private $models = array(
		'M_kontak'              => 'kontak',
		'Label_model'           => 'label',
		'M_province'            => 'province',
		'M_city'                => 'city',
		'M_donation_type'       => 'type',
		'M_donation_kanal'      => 'kanal',
		'M_activity'            => 'activity',
		'M_activity_attachment' => 'activity_attachment',
		'M_users_label'         => 'users_label',
		'M_campaign'            => 'campaign',
		'M_kategori'            => 'kategori',
		'M_setting'             => 'setting',
		'M_metode_pembayaran'   => 'metode_pembayaran',
		'M_donation_multi'      => 'donasi_multi',
		'M_country'             => 'country',
	);

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		foreach ($this->models as $file => $object_name) {
			$this->load->model($file, $object_name);
		}

		$this->data['load_css'] = ['assets/css/mystyle'];
		$this->data['title'] = 'Kontak';
		$this->data['modul'] = strtolower(__CLASS__);

		$logo_setting = $this->setting->where(['name' => 'logo'])->get()->row()->value;
		$this->data['logo'] = base_url('storage/settings/' . $logo_setting);
	}

	// ------------------------------------------------------------------------

	/**
	 * index
	 * Halaman kontak
	 * @return void
	 */
	public function index()
	{
		$this->data['load_js'] = 'kontak/script';

		$this->data['jumlah_kontak'] = $this->kontak->kontak_has_donasi()->num_rows();

		$this->load->view('inc/header', $this->data);
		$this->load->view('kontak/v_kontak', $this->data);
		$this->load->view('inc/footer', $this->data);
	}

	// ------------------------------------------------------------------------

	/**
	 * view_data
	 * Method for list kontak datatables
	 * @return json
	 */
	public function view_data()
	{
		header('Content-Type: application/json');
		$result = $this->kontak->get_daftar_kontak();
		echo json_encode($result);
	}

	// ------------------------------------------------------------------------

	/**
	 * add
	 * Method for modal form add user
	 * @return void
	 */
	public function add()
	{
		if (is_ajax()) {
			
			$this->data['country'] = $this->country->get()->result_array();
			$this->data['sapaan'] = $this->get_enum_values('users', 'sapaan');
			$this->load->view('kontak/add', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * save
	 * Method for save user data ajax/api
	 * @return json
	 */
	public function save()
	{
		$post = $this->input->post();
		$rules = [
			[
				'field' => 'full_name',
				'label' => 'Nama lengkap',
				'rules' => 'required'
			]
		];

		if (empty($post['email'])) {
			$rules[] = [
				'field' => 'phone',
				'label' => 'Nomor whatsapp',
				'rules' => 'required|is_unique[users.phone]',
			];
		}

		if (empty($post['phone'])) {
			$rules[] = [
				'field' => 'email',
				'label' => 'Email',
				'rules' => 'required|valid_email|is_unique[users.email]',
			];
		}

		if (!empty($post['email']) && !empty($post['phone'])) {
			$rules = [
				[
					'field' => 'full_name',
					'label' => 'Nama lengkap',
					'rules' => 'required'
				],
				[
					'field' => 'phone',
					'label' => 'Nomor whatsapp',
					'rules' => 'required|is_unique[users.phone]',
				],
				[
					'field' => 'email',
					'label' => 'Email',
					'rules' => 'required|valid_email|is_unique[users.email]',
				]
			];
		}

		$this->form_validation->set_rules($rules);
		$this->form_validation->set_message('is_unique', 'Maaf, {field} telah terdaftar di daftar kontak');
		try {
			if ($this->form_validation->run() == FALSE) {
				$response = [
					'message'	=> 'Please complete the input!',
					'errors'	=> $this->form_validation->error_array()
				];
				throw new Exception(json_encode($response));
			}

			$data = [
				'email'      => $post['email'],
				'first_name' => $post['full_name'],
				'created_on' => time(),
				'phone'      => $post['phone'],
				'sapaan'     => $post['sapaan']
			];

			$user = $this->kontak->insert($data);

			if (!$user) {
				throw new \Exception($this->db->error());
			}

			$response['status'] = 'success';
			$response['message'] = 'Data saved successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	/**
	 * detail
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function detail($id = null)
	{
		$this->data['title']       = 'Ruang Donatur';
		$this->data['edit']        = $this->kontak->where(['id' => $id])->get()->row_array();
		$this->data['id']          = $this->data['edit']['id'];
		$this->data['konsolidasi'] = $this->donasi_multi->get_konsolidasi($this->data['edit']['id'])->result_array();
		$this->data['labels']      = $this->users_label->where(['id_user' => $id])->with_label()->get()->result_array();
		if (!empty($id)) {
			if (empty($this->data['edit'])) {
				redirect('kontak');
			}
			$this->data['load_js']       = 'kontak/script_detail';
			$this->load->view('inc/header', $this->data);
			$this->load->view('kontak/v_detail', $this->data);
			$this->load->view('inc/footer', $this->data);
		} else {
			redirect('kontak');
		}
	}

	/**
	 * konsolidasi
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function konsolidasi($id = null)
	{
		$this->data['title']         = 'Laporan konsolidasi';
		$this->data['edit']          = $this->kontak->where(['id' => $id])->get()->row_array();
		$this->data['id']            = $id;
		$this->data['konsolidasi']  = $this->donasi_multi->get_konsolidasi($id)->result_array();

		if (!empty($id)) {
			if (empty($this->data['edit'])) {
				redirect('kontak');
			}
			$this->load->view('inc/header', $this->data);
			$this->load->view('kontak/v_konsolidasi', $this->data);
			$this->load->view('inc/footer', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * ajax_detail
	 *
	 * @return void
	 */
	public function ajax_detail()
	{
		$id = $this->input->post('id');
		$this->data['title']         = 'Ruang Donatur';
		$this->data['edit']          = $this->kontak->get_by_id($id)->row_array();
		$this->data['id']            = $id;
		$this->data['konsolidasi']  = $this->donasi_multi->get_konsolidasi($id)->result_array();

		if (is_ajax()) {
			if (empty($this->data['edit'])) {
				redirect('kontak');
			}
			$this->load->view('kontak/detail', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * edit
	 *
	 * @return void
	 */
	public function edit($id = null)
	{
		if (!empty($this->input->post('id'))) {
			$id = $this->input->post('id');
		}

		if (is_ajax()) {

			$this->data['title'] = 'Ruang Donatur';
			$this->data['edit']  = $this->kontak->get_by_id($id)->row_array();

			$phone_code = substr($this->data['edit']['phone'] ,0,2);

			$this->data['iso']       = @$this->country->where(['phone_code' => $phone_code])->get()->row()->iso ? $this->country->where(['phone_code' => $phone_code])->get()->row()->iso : 'ID';
			$this->data['id']        = $id;
			$this->data['status']    = $this->get_enum_values('users', 'status');
			$this->data['citys']     = $this->city->get()->result_array();
			$this->data['provinces'] = $this->province->get()->result_array();
			$this->data['country']   = $this->country->get()->result_array();
			$this->load->view('kontak/edit', $this->data);

		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * update
	 * 
	 * @return json
	 */
	public function update()
	{
		$id = $this->input->post('id');
		$labels = $this->input->post('label[]');
		$post = $this->input->post();
		// validate form input
		$rules = [
			[
				'field' => 'first_name',
				'label' => 'Nama lengkap',
				'rules' => 'required'
			],
		];

		$user = $this->kontak->where(['id' => $id])->get()->row();
		if ($user->email != $this->input->post('email')) {
			$rules[] = [
				'field' => 'email',
				'label' => 'Email',
				'rules' => 'required|valid_email|is_unique[users.email]',
				'errors' => [
					'is_unique' => 'Maaf, {field} telah terdaftar di daftar kontak',
				],
			];
		}
		if ($user->phone != $this->input->post('phone')) {
			$rules[] = [
				'field' => 'phone',
				'label' => 'Nomor whatsapp',
				'rules' => 'required|is_unique[users.phone]',
				'errors' => [
					'is_unique' => 'Maaf, {field} telah terdaftar di daftar kontak',
				],
			];
		}
		if ($this->input->post('id_donatur') != $user->id_donatur) {
			$rules[] = [
				'field' => 'id_donatur',
				'label' => 'ID donatur',
				'rules' => 'required|is_unique[users.id_donatur]',
				'errors' => [
					'is_unique' => 'Maaf, {field} telah digunakan',
				],
			];
		}

		// update the password if it was posted
		if ($this->input->post('password')) {
			$rules[] = [
				'field' => 'password',
				'label' => $this->lang->line('edit_user_validation_password_label'),
				'rules' => 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|matches[password_confirm]'
			];

			$rules[] = [
				'field' => 'password_confirm',
				'label' => $this->lang->line('edit_user_validation_password_confirm_label'),
				'rules' => 'required'
			];
		}

		$this->form_validation->set_rules($rules);
		try {

			if ($this->form_validation->run() == FALSE) {
				$response = [
					'message'	=> 'Please complete the input!',
					'errors'	=> $this->form_validation->error_array()
				];
				throw new Exception(json_encode($response));
			}

			$data = [
				'id_donatur' => $this->input->post('id_donatur'),
				'email' => $this->input->post('email'),
				'first_name' => $this->input->post('first_name'),
				'phone' => $this->input->post('phone'),
				'npwp' => $this->input->post('npwp'),
				'nik' => $this->input->post('nik'),
				'address' => $this->input->post('address'),
				'id_city' => $this->input->post('id_city'),
				'id_province' => $this->input->post('id_province'),
				'countries' => $this->input->post('countries'),
			];

			if (!empty($_FILES['attachments']['name'])) {

				$options = [
					'upload_path' => $this->path_users,
					'name' => 'attachments',
				];

				if (isset($user->attachments)) {
					$options['old_file'] = $user->attachments;
				}

				$upload = $this->do_upload($options);

				if ($upload['status'] == false) {
					throw new Exception($upload['data']);
				}

				$data_users_details['attachments'] = $upload['data']['upload_data']['file_name'];
			}

			if (!empty($this->input->post('password'))) {
				$data_ion_auth = [
					'password' => $this->input->post('password')
				];
				$update = $this->ion_auth->update($id, $data_ion_auth);
			}

			// set body activity
			$kontak_now = $this->kontak->where(['id' => $id])->get()->row_array();
			$body = '';
			$alias = [
				'id_donatur'  => 'ID donatur',
				'email'       => 'email',
				'first_name'  => 'nama',
				'phone'       => 'telepon',
				'npwp'        => 'npwp',
				'nik'         => 'nik',
				'address'     => 'alamat',
				'id_province' => 'provinsi',
				'id_city'     => 'kota',
				'countries'   => 'negara',
			];
			foreach ($data as $key => $item) {
				if ($data[$key] != $kontak_now[$key]) {
					$body .= "mengubah $alias[$key] $kontak_now[$key] menjadi $data[$key], ";
				}
			}

			// update users
			$update = $this->ion_auth->update($id, $data);

			// update label
			if (!empty($labels)) {
				$this->users_label->where(['id_user' => $id])->delete();
				foreach ($labels as $id_label) {
					$this->users_label->insert(['id_label' => $id_label, 'id_user' => $id]);
				}
			}

			// create activity
			if (!empty($body)) {
				$data_activity = [
					'id_by_user'       => $this->ion_auth->user()->row()->id,
					'id_to_user'       => $id,
					'id_activity_type' => 3, // option 1 send email, 2 send whataspp, 3 change kontak
					'body'             => substr($body, 0, -2),
					'date_created'     => date('Y-m-d H:i:s', time())
				];
				$this->activity->insert($data_activity);
			}

			if (!($update)) {
				throw new \Exception($this->db->error());
			}

			$response['status'] = 'success';
			$response['message'] = 'Data updated successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * delete
	 * 
	 * @return json
	 */
	public function delete()
	{
		try {
			$post = $this->input->post();
			$ids = $post['id_delete'];

			$this->db->where_in('user_id',  $ids);
			$this->db->delete('users_groups');

			$this->db->where_in('id',  $ids);
			$this->db->delete('users');

			foreach ($ids as $id) {
				$this->users_label->where(['id_user' => $id])->delete();
			}

			if (empty($ids)) {
				throw new Exception("Not found data for delete!");
			}

			$response['status'] = 'success';
			$response['message'] = 'Data deleted successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	// ------------------------------------------------------------------------	

	/**
	 * view_donation
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function view_data_donation($id_user)
	{
		$post          = $this->input->post();
		$length        = $post['length'];
		$start         = $post['start'];
		$order_by      = 'donation.date_created';
		$order_type    = 'desc';
		$search        = $post['search'];
		$search_column = [
			'donation.nomor_kwitansi'
		];
		$select_field = [
			'donation.date_created',
			'donation.id',
			'donation.id_donation_kanal',
			'donation.attachment',
			'donation.nomor_kwitansi',
			'donation.total',
			'c.campaign',
			'dt.id as id_donation_type',
			'dt.name as type',
			'k.name as kategori',
			'dk.name as kanal'
		];

		$order_index = $post['order'][0]['column'];
		if (!empty($post['columns'][$order_index]['data']) && $post['order'][0]['dir']) {
			$order_by = $post['columns'][$order_index]['data'];
			$order_type = $post['order'][0]['dir'];

			switch ($order_by) {
				case 'first_name':
					$order_by  =  "u.$order_by";
					break;

				case 'kanal':
					$order_by  =  "dk.name";
					break;

				case 'type':
					$order_by  =  "dt.name";
					break;

				default:
					$order_by = 'donation.date_created';
					break;
			}
		}

		$data = $this->donasi_multi->with_donation_item()->with_campaign()->with_kanal()->with_type()->with_kategori()->where(['id_user' => $id_user])->length($length)->start($start)->order($order_by, $order_type)->group('donation.id')->get($select_field)->result_array();

		if (isset($search) && !empty($search)) {
			$data = $this->donasi_multi->with_donation_item()->with_campaign()->with_kanal()->with_type()->with_kategori()->where(['id_user' => $id_user])->like($search_column, $search)->length($length)->start($start)->order($order_by, $order_type)->group('donation.id')->get($select_field)->result_array();
		}

		$total_record_filter = $this->donasi_multi->with_donation_item()->with_campaign()->with_kanal()->with_type()->with_kategori()->where(['id_user' => $id_user])->get('count(*) as record')->row()->record;


		$response["draw"]            = isset($post['draw']) ? intval($post['draw']) : 0;
		$response["recordsTotal"]    = intval(($total_record_filter));
		$response["recordsFiltered"] = intval(($total_record_filter));
		$response["data"]            = $data;
		return response($response);
	}

	// ------------------------------------------------------------------------

	/**
	 * get_city
	 *
	 * @return html
	 */
	public function get_city()
	{
		$id_province = $this->input->post('id_province');
		$citys = $this->city->where(['id_province' => $id_province])->get()->result_array();
		if (empty($citys)) {
			$response['status'] = 'failed';
			$response['message'] = 'Data not found!';
		} else {
			$response['data'] = $citys;
			$response['status'] = 'success';
			$response['message'] = 'Data existed!';
		}
		return response($response);
	}

	// ------------------------------------------------------------------------

	/**
	 * filter
	 *
	 * @return void
	 */
	public function filter()
	{
		$this->data['search_by'] = [
			[
				'key' => 'first_name',
				'label' => 'Nama'
			],
			[
				'key' => 'phone',
				'label' => 'Telepon'
			],
			[
				'key' => 'email',
				'label' => 'Email'
			],
		];

		$this->data['jumlah_donasi'] = [
			[
				'key' => '0-100.000',
				'label' => '0 s/d 100.000'
			],
			[
				'key' => '100.000-500.000',
				'label' => '100.000 s/d 500.000'
			],
			[
				'key' => '500.000-1.000.000',
				'label' => '500.000 s/d 1.000.000'
			],
			[
				'key' => '1.000.000-10.000.000',
				'label' => '1.000.000 s/d 10.000.000'
			],
			[
				'key' => '10.000.000-0',
				'label' => 'diatas 10.000.000'
			],
		];

		$this->data['jumlah_trx'] = [
			[
				'key' => '0-10',
				'label' => '0 s/d 10'
			],
			[
				'key' => '10-100',
				'label' => '10 s/d 100'
			],
			[
				'key' => '100-0',
				'label' => 'diatas 100'
			],
		];

		$kanal = $this->kanal->get()->result_array();
		foreach ($kanal as $k => $v) {
			$kanal[$k]['metode'] = $this->metode_pembayaran->where(['id_donation_kanal' => $v['id']])->get()->result_array();
		}

		$this->data['kanals']    = $kanal;
		$this->data['labels'] = $this->label->all()->result_array();
		$this->data['types'] = $this->type->get()->result_array();
		$this->data['kategoris'] = $this->kategori->get()->result_array();
		$this->data['campaigns'] = $this->campaign->get()->result_array();
		array_unshift($this->data['kanals'], [
			'id' => 'semua',
			'name' => 'Semua kanal'
		]);
		array_unshift($this->data['types'], [
			'id' => 'semua',
			'name' => 'Semua jenis donasi'
		]);
		array_unshift($this->data['kategoris'], [
			'id' => 'semua',
			'name' => 'Semua kategori'
		]);
		array_unshift($this->data['campaigns'], [
			'id' => 'semua',
			'campaign' => 'Semua campaign'
		]);
		$this->load->view('kontak/filter', $this->data);
	}

	// ------------------------------------------------------------------------

	/**
	 * get_label
	 *
	 * @return void
	 */
	public function get_label()
	{
		try {
			$data = $this->label->all()->result_array();

			$response['status'] = 'success';
			$response['data'] = $data;
			$response['message'] = 'Data deleted successfully!';
			return response($response);
		} catch (\Exception $e) {
			$response['status'] = 'failed';
			$response['message'] = $e->getMessage();
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * edit_label
	 *
	 * @return void
	 */
	public function edit_label()
	{
		try {
			$ids_kontak = $this->input->post('id_kontak');
			$ids_label = $this->input->post('id_label');

			foreach ($ids_kontak as $id_user) {
				foreach ($ids_label as  $id_label) {
					// cek label sudah ada belum di user ini 
					$check_label = $this->users_label->where(['id_user' => $id_user, 'id_label' => $id_label])->get()->num_rows();
					if ($check_label < 1) {
						$this->users_label->insert(['id_label' => $id_label, 'id_user' => $id_user]);
					}
				}
			}
			$response['status'] = 'success';
			$response['message'] = 'Data updated successfully!';
			return response($response);
		} catch (\Exception $e) {
			$response['status'] = 'failed';
			$response['message'] = $e->getMessage();
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * get_activity
	 *
	 * @return void
	 */
	public function get_activity()
	{
		$page_activity = $_POST['page_activity'];
		$length = 5;
		$start = ($page_activity * $length) - $length;

		$id = $_POST['id_user'];

		$select_field = [
			'activity.id',
			'activity.id_by_user',
			'activity.id_to_user',
			'activity.date_created',
			'activity.body',
			'u1.first_name as user_by',
			'u2.first_name as user_to',
			'at.id as id_activity_type',
			'at.name',
			'at.icon'
		];
		
		$perday = $this->activity->where(['id_by_user' => $id])->or_where(['id_to_user' => $id])->length($length)->start($start)->order('activity.date_created', 'desc')->group("DATE_FORMAT(date_created, '%Y-%m-%d')")->get(['activity.id', 'activity.date_created'])->result_array();

		foreach ($perday as $k => $v) {

			$format_from_date = date('Y-m-d 00:00:00', strtotime($v['date_created']));
			$format_to_date = date('Y-m-d 23:59:59', strtotime($v['date_created']));

			$where = [
				'activity.id_by_user' => $id,
				'activity.date_created >=' => $format_from_date,
				'activity.date_created <=' => $format_to_date
			];
			$or_where = [
				'activity.id_to_user' => $id,
				'activity.date_created >=' => $format_from_date,
				'activity.date_created <=' => $format_to_date
			];
			$perday[$k]['activity_detail'] = $this->activity->with_user()->with_activity_type()->where($where)->or_where($or_where)->length($length)->start($start)->order('activity.date_created', 'desc')->get($select_field, TRUE)->result_array();
			$perday[$k]['date'] = time_elapsed_string($v['date_created']);
			$perday[$k]['date_created'] = date('M d, Y', strtotime($v['date_created']));

			foreach ($perday[$k]['activity_detail'] as $kk => $vv) {
				if ($vv['id_activity_type'] == 2 || $vv['id_activity_type'] == 1) {
					$perday[$k]['activity_detail'][$kk]['attachments'] = $this->activity_attachment->where(['id_activity' => $vv['id']])->get()->result_array();
					$perday[$k]['activity_detail'][$kk]['time'] = date('H:i A', strtotime($vv['date_created']));
				} else {
					$perday[$k]['activity_detail'][$kk]['time'] = date('H:i A', strtotime($vv['date_created']));
				}
			}
		}

		$result = $perday;

		$response["status"] = 'success';
		$response["data"] = $result;
		$response["recordsFiltered"] = count($result);
		return response($response);
	}

	// ------------------------------------------------------------------------

	/**
	 * send_whatsapp
	 *
	 * @return void
	 */
	public function send_whatsapp()
	{
		if (is_ajax()) {
			$this->data['id'] = $this->input->post('id');
			$this->load->view('kontak/send_whatsapp', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * save_send_whatsapp
	 *
	 * @return void
	 */
	public function save_send_whatsapp()
	{
		// validate form input
		$rules = [
			[
				'field' => 'pesan',
				'label' => 'Pesan',
				'rules' => 'required'
			],
		];

		// load kumis
		$this->load->library('kumis');


		$this->form_validation->set_rules($rules);
		try {

			if ($this->form_validation->run() == FALSE) {
				$response = [
					'message'	=> 'Please complete the input!',
					'errors'	=> $this->form_validation->error_array()
				];
				throw new Exception(json_encode($response));
			}

			// save activity
			$pesan = $_POST['pesan'];
			$data = [
				'id_by_user'      => $this->ion_auth->user()->row()->id,
				'id_to_user'      => $this->input->post('id'),
				'id_activity_type' => 2, // option 1 send email, 2 send whataspp, 3 change kontak,
				'body' => $pesan,
			];
			$id_activity = $this->activity->insert($data);
			$activity = $this->activity->where(['id' => $id_activity])->get()->row();

			if (!empty($_FILES['attachments0']['name'])) {

				$i = 0;
				foreach ($_FILES as $file) {
					$options = [
						'upload_path' => FCPATH . '/storage/activity',
						'name' => 'attachments' . $i,
					];
					$upload = $this->do_upload($options);

					if ($upload['status'] == false) {
						$response = [
							'message'	=> strip_tags($upload['data']['error']),
						];
						$this->activity_attachment->where(['id' => $id_activity])->delete();
						throw new Exception(json_encode($response));
					}

					$filename = $upload['data']['upload_data']['file_name'];
					$data_activity_attachment = [
						'id_activity' => $id_activity,
						'name' => $filename
					];

					$this->activity_attachment->insert($data_activity_attachment);
					$i++;
				}
			}

			// send to whatsapp
			$number = $this->kontak->where(['id' => $activity->id_to_user])->get()->row()->phone;
			$cek_number = kumis::is_wa_number($number);
			$array_cek_number = json_decode($cek_number, true);
			if ($array_cek_number['status'] == 'error') {
				$response['status'] = 'warning';
				$response['message'] = 'Pesan gagal terkirm, nomor belum terdaftar di whatsapp!';
				return response($response);
			}
			$message = $activity->body;
			kumis::send_message($number, $message); //text
			$activity_attachment = $this->activity_attachment->where(['id_activity' => $activity->id])->get();
			if ($activity_attachment->num_rows() > 0) {
				foreach ($activity_attachment->result_array() as $item) {
					$file = $item['name'];
					$url = base_url('storage/activity/') . $file;
					$x_file = explode('.', $file);
					if ($x_file[1] == 'pdf') {
						kumis::send_file($number, $url); //file
					} else {
						kumis::send_image($number, $url); //image
					}
				}
			}

			$response['status'] = 'success';
			$response['message'] = 'Whatsapp message sended successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * send_email
	 *
	 * @return void
	 */
	public function send_email()
	{
		if (is_ajax()) {
			$this->data['id'] = $this->input->post('id');
			$this->load->view('kontak/send_email', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * save_send_email
	 *
	 * @return void
	 */
	public function save_send_email()
	{
		// validate form input
		$rules = [
			[
				'field' => 'pesan',
				'label' => 'Pesan',
				'rules' => 'required'
			],
		];

		$this->form_validation->set_rules($rules);
		try {

			if ($this->form_validation->run() == FALSE) {
				$response = [
					'message'	=> 'Please complete the input!',
					'errors'	=> $this->form_validation->error_array()
				];
				throw new Exception(json_encode($response));
			}

			// save activity
			$pesan = $_POST['pesan'];
			$data = [
				'id_by_user'      => $this->ion_auth->user()->row()->id,
				'id_to_user'      => $this->input->post('id'),
				'id_activity_type' => 1, // option 1 send email, 2 send whataspp, 3 change kontak,
				'body' => $pesan,
			];
			$id_activity = $this->activity->insert($data);
			$activity = $this->activity->where(['id' => $id_activity])->get()->row();

			if (!empty($_FILES['attachments0']['name'])) {

				$i = 0;
				foreach ($_FILES as $file) {
					$options = [
						'upload_path' => FCPATH . '/storage/activity',
						'name' => 'attachments' . $i,
					];
					$upload = $this->do_upload($options);

					if ($upload['status'] == false) {
						throw new Exception($upload['data']);
					}

					$filename = $upload['data']['upload_data']['file_name'];
					$data_activity_attachment = [
						'id_activity' => $id_activity,
						'name' => $filename
					];
					$this->activity_attachment->insert($data_activity_attachment);
					$i++;
				}
			}

			// send email
			$this->load->library('mailketing');
			$recipient = $this->kontak->where(['id' => $activity->id_to_user])->get()->row()->email;
			$from_name = $this->ion_auth->user()->row()->first_name;
			$cc = $this->input->post('cc');
			$subject = $this->input->post('subject');
			$content = $activity->body;
			$files = [];
			$activity_attachment = $this->activity_attachment->where(['id_activity' => $activity->id])->get();
			if ($activity_attachment->num_rows() > 0) {
				foreach ($activity_attachment->result_array() as $item) {
					$file = $item['name'];
					$url = base_url('storage/activity/') . $file;
					array_push($files, $url);
				}
			}

			mailketing::smtp_send($recipient, $subject, $content, $files, $from_name, $cc, null);
			$response['status'] = 'success';
			$response['message'] = 'Email message sended successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * import
	 *
	 * @return void
	 */
	public function import()
	{
		if (is_ajax()) {
			$this->data['labels'] = $this->label->all()->result_array();
			$this->load->view('kontak/import', $this->data);
		} else {
			show_404();
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * action_import
	 *
	 * @return void
	 */
	public function action_import()
	{
		$rules = [];
		// attachments required
		if (empty($_FILES['file']['name'])) {
			$rules[] =	[
				'field' => 'file',
				'label' => 'File',
				'rules' => 'required'
			];
		}

		$this->form_validation->set_rules($rules);
		try {

			if (empty($_FILES['file']['name'])) {
				if ($this->form_validation->run() == FALSE) {
					$message = '';
					$i = 0;
					foreach ($this->form_validation->error_array() as $key => $value) {
						if ($i == 0) {
							$message = $value;
						}
						$i++;
					}
					$response = [
						'message'	=> $message,
						'errors'	=> $this->form_validation->error_array()
					];
					throw new Exception(json_encode($response));
				}
			}

			$path =  rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR;
			$options = [
				'upload_path' => $path,
				'name' => 'file',
				'allowed_types' => 'xlsx|xls',
			];
			$upload = $this->do_upload($options);

			if ($upload['status'] == false) {
				$msg = "Upload gagal atau format file tidak didukung!";
				$response = [
					'message'	=> $msg,
					'errors'	=> $upload
				];
				throw new Exception(json_encode($response));
			}
			$filename = $upload['data']['upload_data']['file_name'];
			$x_filename = explode('.', $filename);
			$file_xls = $path . $filename;
			$labels = $this->input->post('labels[]');

			if ($x_filename[1] === 'xlsx') {
				if ($xls = SimpleXLSX::parse($file_xls)) {
					$i = 0;
					$data = [];
					foreach ($xls->rows() as $item) {
						if ($i > 0) {
							if (!empty($item[2])) {
								$x_time = explode(' ', $item[0]);
								$time = $x_time[0] . ' ' . date('H:i:s', time());
								$x_city = explode('-', $item[9]);
								$x_province = explode('-', $item[8]);
								$d_user = [
									'email'       => $item[4],
									'first_name'  => $item[2],
									'created_on'  => strtotime($time),
									'phone'       => $item[3],
									'sapaan'      => $item[1],
									'npwp'        => $item[5],
									'nik'         => $item[6],
									'address'     => $item[7],
									'id_city'     => $item[9],
									'id_province' => $item[8],
									'countries'   => $item[10],
								];
								array_push($data, $d_user);
							}
						}
						$i++;
					}
					$save = $this->kontak->import($data, $labels);
					if (!$save) {
						throw new Exception('Gagal menyimapan data atau data duplicate!');
					}
					@unlink($file_xls);
				} else {
					throw new Exception(SimpleXLSX::parseError());
				}
			} else {
				if ($xls = SimpleXLS::parse($file_xls)) {
					$i = 0;
					$data = [];
					foreach ($xls->rows() as $item) {
						if ($i > 0) {
							if (!empty($item[2])) {
								$x_time = explode(' ', $item[0]);
								$time = $x_time[0] . ' ' . date('H:i:s', time());
								$x_city = explode('-', $item[9]);
								$x_province = explode('-', $item[8]);
								$d_user = [
									'email'       => $item[4],
									'first_name'  => $item[2],
									'created_on'  => strtotime($time),
									'phone'       => $item[3],
									'sapaan'      => $item[1],
									'npwp'        => $item[5],
									'nik'         => $item[6],
									'address'     => $item[7],
									'id_city'     => $item[9],
									'id_province' => $item[8],
									'countries'   => $item[10],
								];
								array_push($data, $d_user);
							}
						}
						$i++;
					}
					$save = $this->kontak_model->import($data, $labels);
					if (!$save) {
						throw new Exception('Gagal menyimapan data atau data duplicate!');
					}
					@unlink($file_xls);
				} else {
					throw new Exception(SimpleXLS::parseError());
				}
			}


			$response['status'] = 'success';
			$response['message'] = 'Import saved successfully!';
			return response($response);
		} catch (\Exception $e) {
			$exception	= json_decode($e->getMessage());
			$response['status'] = 'failed';
			$response['message'] =  isset($exception->message) ? $exception->message : $e->getMessage();
			if (isset($exception->errors)) {
				$response['errors'] = $exception->errors;
			}
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * pdf_konsolidasi
	 *
	 * @param  mixed $id_user
	 * @param  mixed $tahun
	 * @return void
	 */
	public function pdf_konsolidasi($id_user, $tahun)
	{
		$this->data['tahun']		= $tahun;
		$this->data['user']         = $this->kontak->where(['id' => $id_user])->get()->row_array();
		$this->data['jenis_donasi'] = $this->type->get()->result_array();
		$this->data['total'] = 0;
		foreach ($this->data['jenis_donasi'] as $key => $item) {
			$this->data['jenis_donasi'][$key]['total'] = $this->donasi_multi->get_konsolidasi($id_user, $tahun, $item['id'])->row()->total;
			$this->data['total'] += intval($this->data['jenis_donasi'][$key]['total']);
		}
		$this->data['donasi']       = $this->donasi_multi->get_konsolidasi($id_user, $tahun)->result_array();
		$html = $this->load->view('kontak/pdf_konsolidasi', $this->data, TRUE);

		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);
		$dompdf->set_option('isRemoteEnabled', true);
		$dompdf->setPaper('A4', 'potrait');
		$dompdf->render();
		$dompdf->stream("konsolidasi_drm.pdf", array("Attachment" => false));
	}

	// ------------------------------------------------------------------------


	/**
	 * html_konsolidasi
	 *
	 * @param  mixed $id_user
	 * @param  mixed $tahun
	 * @return void
	 */
	public function html_konsolidasi($id_user, $tahun)
	{
		$this->data['tahun']		= $tahun;
		$this->data['user']         = $this->kontak->where(['id' => $id_user])->get()->row_array();
		$this->data['jenis_donasi'] = $this->type->get()->result_array();
		$this->data['total'] = 0;
		foreach ($this->data['jenis_donasi'] as $key => $item) {
			$this->data['jenis_donasi'][$key]['total'] = $this->donasi_multi->get_konsolidasi($id_user, $tahun, $item['id'])->row()->total;
			$this->data['total'] += intval($this->data['jenis_donasi'][$key]['total']);
		}
		$this->data['donasi']       = $this->donasi_multi->get_konsolidasi($id_user, $tahun)->result_array();
		$this->load->view('kontak/html_konsolidasi', $this->data);
	}

	// ------------------------------------------------------------------------

	/**
	 * example_import
	 *
	 * @return void
	 */
	public function example_import()
	{
		$faker = Faker\Factory::create('id_ID');
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet()->setTitle('Kontak');
		$alpha = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

		// sheet 1
		$headers = [
			'TANGGAL',
			'SAPAAN',
			'NAMA LENGKAP',
			'NOMOR WHATSAPP',
			'EMAIL',
			'NPWP',
			'NIK',
			'STATUS',
			'ALAMAT',
			'PROVINSI',
			'KOTA',
			'NEGARA'
		];
		$from = '';
		$to = '';
		for ($i = 0; $i < count($headers); $i++) {
			if ($i == 0) {
				$from = $alpha[$i] . '1';
			}
			if ($i == (count($headers) - 1)) {
				$to = $alpha[$i] . '1';
			}
			$sheet->setCellValue($alpha[$i] . '1', $headers[$i]);
		}
		$sheet->getStyle("$from:$to")->getFont()->setBold(true);

		//  isi sheet 1
		$sapaans = $this->get_enum_values('users', 'sapaan');
		$provinces = $this->province->get()->result_array();
		$citys = $this->city->get()->result_array();
		for ($i = 2; $i < 7; $i++) {
			$key = rand(0, 3);
			$key2 = rand(0, 10);
			$array_sapaan = (array)$sapaans[$key];
			$sheet->setCellValue('A' . $i, date('Y-m-d', strtotime('-' . rand(0, 4) . ' day', time())));
			$sheet->setCellValue('B' . $i, $array_sapaan[0]);
			$sheet->setCellValue('C' . $i, $faker->firstName);
			$sheet->setCellValue('D' . $i, $faker->phoneNumber);
			$sheet->setCellValue('E' . $i, $faker->email);
			$sheet->setCellValue('F' . $i, $faker->randomNumber(5, true));
			$sheet->setCellValue('G' . $i, $faker->randomNumber(8, true));
			$sheet->setCellValue('H' . $i, 'Donatur');
			$sheet->setCellValue('I' . $i, $faker->address);
			$sheet->setCellValue('J' . $i, $provinces[$key2]['id'] . '-' . $provinces[$key2]['name']);
			$sheet->setCellValue('K' . $i, $citys[$key2]['id'] . '-' . $citys[$key2]['name']);
			$sheet->setCellValue('L' . $i, 'Indonesia');
		}

		// sheet 2
		$spreadsheet->createSheet()->setTitle('Master Sapaan');
		$sheet2 = $spreadsheet->getSheetByName('Master Sapaan');
		$headers = ['SAPAAN'];
		$from = '';
		$to = '';
		for ($i = 0; $i < count($headers); $i++) {
			if ($i == 0) {
				$from = $alpha[$i] . '1';
			}
			if ($i == (count($headers) - 1)) {
				$to = $alpha[$i] . '1';
			}
			$sheet2->setCellValue($alpha[$i] . '1', $headers[$i]);
		}
		$sheet2->getStyle("$from:$to")->getFont()->setBold(true);

		//  isi sheet 2
		$i = 2;
		foreach ($sapaans as $data) {
			$sheet2->setCellValue('A' . $i, $data);
			$i++;
		}

		// sheet 3
		$spreadsheet->createSheet()->setTitle('Master provinsi');
		$sheet3 = $spreadsheet->getSheetByName('Master provinsi');
		$headers = ['ID', 'PROVINSI'];
		$from = '';
		$to = '';
		for ($i = 0; $i < count($headers); $i++) {
			if ($i == 0) {
				$from = $alpha[$i] . '1';
			}
			if ($i == (count($headers) - 1)) {
				$to = $alpha[$i] . '1';
			}
			$sheet3->setCellValue($alpha[$i] . '1', $headers[$i]);
		}
		$sheet3->getStyle("$from:$to")->getFont()->setBold(true);

		//  isi sheet 3
		$i = 2;
		foreach ($provinces as $data) {
			$sheet3->setCellValue('A' . $i, $data['id']);
			$sheet3->setCellValue('B' . $i, $data['id'] . '-' . $data['name']);
			$i++;
		}

		// sheet 4
		$spreadsheet->createSheet()->setTitle('Master kota');
		$sheet4 = $spreadsheet->getSheetByName('Master kota');
		$headers = ['ID', 'KOTA'];
		$from = '';
		$to = '';
		for ($i = 0; $i < count($headers); $i++) {
			if ($i == 0) {
				$from = $alpha[$i] . '1';
			}
			if ($i == (count($headers) - 1)) {
				$to = $alpha[$i] . '1';
			}
			$sheet4->setCellValue($alpha[$i] . '1', $headers[$i]);
		}
		$sheet4->getStyle("$from:$to")->getFont()->setBold(true);

		//  isi sheet 4
		$i = 2;
		foreach ($citys as $data) {
			$sheet4->setCellValue('A' . $i, $data['id']);
			$sheet4->setCellValue('B' . $i, $data['id'] . '-' . $data['name']);
			$i++;
		}

		$spreadsheet->setActiveSheetIndex(0);

		$writer = new Xlsx($spreadsheet);
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="contoh_kontak.xlsx"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
	}

	// ------------------------------------------------------------------------

	/**
	 * export_kontak
	 *
	 * @return void
	 */
	public function export_kontak()
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet()->setTitle('Kontak');
		$alpha = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

		// sheet 1
		$headers = [
			'TANGGAL',
			'SAPAAN',
			'NAMA LENGKAP',
			'NOMOR WHATSAPP',
			'EMAIL',
			'NPWP',
			'NIK',
			'STATUS',
			'ALAMAT',
			'PROVINSI',
			'KOTA',
			'NEGARA',
			'LABEL'
		];
		$from = '';
		$to = '';
		for ($i = 0; $i < count($headers); $i++) {
			if ($i == 0) {
				$from = $alpha[$i] . '1';
			}
			if ($i == (count($headers) - 1)) {
				$to = $alpha[$i] . '1';
			}
			$sheet->setCellValue($alpha[$i] . '1', $headers[$i]);
		}
		$sheet->getStyle("$from:$to")->getFont()->setBold(true);

		$kontaks = $this->kontak->get_kontak_export($_GET);

		$i = 2;
		foreach ($kontaks as $item) {
			$sheet->setCellValue('A' . $i, $item['created_on']);
			$sheet->setCellValue('B' . $i, $item['sapaan']);
			$sheet->setCellValue('C' . $i, $item['first_name']);
			$sheet->setCellValue('D' . $i, $item['phone']);
			$sheet->setCellValue('E' . $i, $item['email']);
			$sheet->setCellValue('F' . $i, $item['npwp']);
			$sheet->setCellValue('G' . $i, $item['nik']);
			$sheet->setCellValue('H' . $i, $item['status']);
			$sheet->setCellValue('I' . $i, $item['address']);
			$sheet->setCellValue('J' . $i, $item['id_province']);
			$sheet->setCellValue('K' . $i, $item['id_city']);
			$sheet->setCellValue('L' . $i, $item['countries']);
			$sheet->setCellValue('M' . $i, $item['labels']);
			$i++;
		}


		$spreadsheet->setActiveSheetIndex(0);
		foreach (range('A', 'M') as $letra) {
			$spreadsheet->getActiveSheet()->getColumnDimension($letra)->setAutoSize(true);
		}
		$filename = date('d-m-Y', time()) . '_kontak_export' . '.xlsx';
		$writer = new Xlsx($spreadsheet);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");;
		header("Content-Disposition: attachment;filename=$filename");
		$writer->save('php://output');
		set_time_limit(30);
		exit;
	}

	// ------------------------------------------------------------------------

	public function edit_label_detail()
	{
		if (is_ajax()) {
			$id_user = $this->input->post('id_user');
			$user_label = [];
			$get_user_label = $this->users_label->where(['id_user' => $id_user])->with_label()->get()->result_array();
			foreach ($get_user_label as  $d) {
				array_push($user_label, $d['id']);
			}
			$labels = $this->label->all()->result_array();
			$data = [
				'labels' => $labels,
				'user_label' => $user_label,
				'user_label_name' => $get_user_label,
			];
			return response(['status' => 'success', 'data' => $data, 'message' => 'Succesfully!']);
		} else {
			return response(['status' => 'failed', 'message' => 'Not found method']);
		}
	}

	// ------------------------------------------------------------------------

	public function update_label_detail()
	{
		if (is_ajax()) {
			$id_user = $this->input->post('id_user');
			$labels = $this->input->post('labels');
			$this->users_label->where(['id_user' => $id_user])->delete();
			if ($labels) {
				foreach ($labels as $d) {
					$data = [
						'id_user' => $id_user,
						'id_label' => $d,
					];
					$this->users_label->insert($data);
				}
			}
			$respon = [
				'id_user' => $id_user
			];
			return response(['status' => 'success', 'data' => $respon, 'message' => 'Data updated succesfully!']);
		} else {
			return response(['status' => 'failed', 'message' => 'Not found method']);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * format_import
	 *
	 * @return void
	 */
	public function format_import()
	{
		$path =  FCPATH . '/storage/import/format-import-excel.xlsx';
		$name = 'format-import-excel.xlsx';
		// make sure it's a file before doing anything!
		if (is_file($path)) {
			// required for IE
			if (ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}

			// get the file mime type using the file extension
			$this->load->helper('file');

			$mime = get_mime_by_extension($path);

			// Build the headers to push out the file properly.
			header('Pragma: public');     // required
			header('Expires: 0');         // no cache
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
			header('Cache-Control: private', false);
			header('Content-Type: ' . $mime);  // Add the mime type from Code igniter.
			header('Content-Disposition: attachment; filename="' . basename($name) . '"');  // Add the file name
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . filesize($path)); // provide file size
			header('Connection: close');
			readfile($path); // push it out
		}
	}

	// ------------------------------------------------------------------------

	public function update_catatan()
	{
		if(is_ajax()){
			$id_user = $this->input->post('id_user');
			$catatan = $this->input->post('catatan');
			$this->kontak->where(['id' => $id_user])->update(['catatan' => $catatan]);

			$response['status'] = 'success';
			$response['message'] = 'Data updated successfully!';
			return response($response);

		}else{
			$response['status'] = 'failed';
			$response['message'] = 'Request not allowed!';
			return response($response);
		}
	}

	// ------------------------------------------------------------------------

	public function get_country()
	{
		if(is_ajax()){

			$where = [];

			if(@$this->input->post('iso'))
			{
				$where['iso'] = $this->input->post('iso');
			}

			if(@$this->input->post('phone_code'))
			{
				$where['phone_code'] = $this->input->post('phone_code');
			}

			$result = $this->country->where($where)->get()->row_array();

			$response['status'] = 'success';
			$response['data'] = $result;
			$response['message'] = 'Data updated successfully!';
			return response($response);
		}else{
			show_404();
		}

	}
}

/* End of file Kontak.php and path /application/controllers/kontak.php */
