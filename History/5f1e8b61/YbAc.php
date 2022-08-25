<!-- WYSIWYG Editor js -->
<script src="<?php echo base_url() ?>/assets/plugins/jquery.richtext/jquery.richtext.js"></script>
<!-- moment js -->
<script src="<?php echo base_url('assets/js/') ?>moment.min.js"></script>
<script>
	var title = '<?php echo $title; ?>'
	var render_number = $.fn.dataTable.render.number('.', ',', 0).display;
	var table_detail_donation = $('#table-detail-donation').DataTable({
		ajax: {
			url: `${modul_url}/view_data_donation/<?php echo isset($id) ? $id : 0  ?>`,
			type: 'post',
			data: function(d) {
				d.search = $('input[name="search_donation"]').val()
			}
		},
		columns: [{
				"data": "date_created",
				render: function(data, type, row, meta) {
					const is_today = moment(data).isSame(moment(), 'day');
					return is_today ? moment(data).format('HH:mm:ss') : moment(data).format('DD-MM-YYYY HH:mm:ss')				}
			},
			{
				"data": null,
				render: function(data, type, row, meta) {
					return `<a class="link" target="_blank" href="${site_url}/donation/detail/${data.id}" >${data.nomor_kwitansi} </a>`
				}
			},
			{
				"data": "kanal"
			},
			{
				"data": "total",
				sClass: "text-right",
				render: function(data, type, row, meta) {
					return render_number(data);
				}
			},
			{
				"data": null,
				sClass: "text-center",
				render: function(data, type, row, meta) {
					return (data.attachment !== '-' && data.attachment !== '' && data.attachment !== null) ? `<a href="#" class="" onClick="show_img('${donasi_storage_url}/${data.attachment}', 'Lampiran donasi No.${data.nomor_kwitansi}')"><i class="fa fa-paperclip"></i></a>` : ``
				}
			},
			{
				"data": null,
				sClass: "text-center",
				"width": '100',
				render: function(data, type, row, meta) {
					var action = `<div class="dropdown dropleft">
									<a id="ellipsmenu" style="color: #867BDD;font-size:1em;cursor:pointer;padding:10px" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
									<br>
								<div class="dropdown-menu dropdown-primary" id="ellipsmenu-content">
										<a href="${site_url}/donation/edit_multi/${data.id}" class="dropdown-item"><i class="fa fa-edit"></i> edit</a>
										<a class="dropdown-item" onclick="btn_delete('${data.id}')"><i class="fa fa-trash"></i> hapus</a>
										<a href="${site_url}/donation/kwitansi/${data.id}/html" class="dropdown-item" target="_blank"><i class="fa fa-eye"></i> lihat kwitansi</a>
										<a href="${site_url}/donation/kwitansi/${data.id}" class="dropdown-item" target="_blank"><i class="fa fa-file-pdf"></i> download kwitansi</a>
										<a class="dropdown-item" onclick="send_email_donation('${data.id}')"><i class="fa fa-envelope"></i> kirim kwitansi via email</a>
										<a class="dropdown-item" onclick="send_whatsapp_donation('${data.id}')"><i class="fab fa-whatsapp"></i> kirim kwitansi via whatsapp</a>
									</div>
								</div>`
					return action
				},
			}
		],
		columnDefs: [{
			targets: 5,
			orderable: false
		}],
		order: [
			[0, "desc"]
		],
		lengthChange: false,
		searching: false,
		responsive: true,
		serverSide: true,
		ordering: true,
		processing: true,
	})

	load_activity()

	function load_activity(page_activity = 1) {
		if (page_activity === 1) {
			sessionStorage.setItem("page_activity", 1);
			$('#activity').html('')
		}
		let id_user = '<?php echo $id ?>';

		$.ajax({
			url: `${modul_url}/get_activity`,
			async: false,
			type: 'post',
			data: {
				page_activity: page_activity,
				id_user: id_user,
			},
			dataType: "json",
			success: function(response) {
				response.data.map((item) => {

					var activity_details = ''
					item.activity_detail.map((detail, index) => {
						var body = `<p class="text-sm">${detail.body}</p>`
						if (detail.id_activity_type == 2 || detail.id_activity_type == 1) {
							var attachments = ''
							detail.attachments.map((attachment) => {
								var x_attachment = attachment.name.split('.')
								if (x_attachment[1] == 'pdf') {
									attachments += `<div class="col-lg-3 col-md-6 col-6 d-flex justify-content-center align-items-center"><a href="${activity_storage_url}/${attachment.name}" target="_blank"><i class="fa fa-file-pdf-o fa-2x"></i></a> </div>`
								} else {
									attachments += `<div class="col-lg-3 col-md-6 col-6"><a href="javascript:void(0);" onClick="show_img('${activity_storage_url}/${attachment.name}')"><img src="${activity_storage_url}/${attachment.name}" alt="" class="img-fluid img-thumbnail mt-2 mb-2"></a> </div>`
								}
							})


							body = `<p class="text-sm">${detail.body}</p>
								<div class="row">
									${attachments}
								</div>`
						}

						activity_details += `<div class="avatar text-center">
													${detail.icon}
												</div>
												<div class="notification-card">
													<div class="notification-header">
														<svg class="icon" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="bell" class="svg-inline--fa fa-bell fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
															<path fill="currentColor" d="M224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64zm215.39-149.71c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71z"></path>
														</svg>
														<div class="notification-title">${detail.user_by}</a> <span class="text-muted">${detail.name} ${detail.user_to}</div>
														<div class="notification-time">${detail.time}</div>
													</div>
													<div class="notification-content">
														${body}
													</div>
												</div>`
					})

					var html = `<section class="notification-section">
											<div class="group-header">
												<div class="group-title">${item.date}</div>
												<div class="group-date">${item.date}, ${item.date_created}</div>
											</div>
											<div class="notification-grid">
												${activity_details}
											</div>
										</section>`
					$('#activity').append(html)
				})
			},
			error: function() {
				alertError('Something wrong!')
			}
		})
	}

	function more_activity() {
		var page_activity = sessionStorage.getItem('page_activity')
		if (typeof page_activity == "undefined") {
			var page_activity = 1
		}
		var next_page = parseInt(page_activity) + 1
		sessionStorage.removeItem('page_activity')
		sessionStorage.setItem("page_activity", next_page);
		var next_page = sessionStorage.getItem('page_activity')
		load_activity(next_page)
	}

	function toObject(keys, values) {
		var result = {};
		for (var i = 0; i < keys.length; i++)
			result[keys[i]] = values[i];
		return result;
	}

	function add_donation(id) {
		Modal.create('Tambah donasi', 'save_donation')
		$.post(`${site_url}/donation/add`, {
			id_user: id
		}, function(result) {
			Modal.html(result)
			set_form_add(true)
			cainable_form_add()
		})
	}

	function set_form_add(is_add = false) {

		$('#nama-donatur').select2({
			placeholder: 'Cari nama kontak',
			allowClear: true,
			ajax: ({
				url: `${modul_url}/search_donatur`,
				method: 'post',
				delay: 250,
				dataType: 'json',
				data: function(params) {
					return {
						q: params.term
					}
				},
				processResults: function(data) {
					return {
						results: $.map(data, function(item) {
							return {
								text: `${item.first_name} (${item.email})`,
								id: item.id
							}
						})
					};
				}
			}),
		})
		var tgl_trx = $('input[name="tgl_trx"]').val()
		$('input[name="tgl_trx"]').datepicker({
			format: 'dd/mm/yyyy',
			autoclose: true,
			endDate: is_add ? new Date() : false
		}).datepicker("setDate", tgl_trx ? tgl_trx : 'now');

		var input_select2 = [{
				name: 'id_donation_kanal',
				placeholder: 'Pilih kanal...'
			},
			{
				name: 'id_donation_type',
				placeholder: 'Pilih jenis donasi...'
			},
			{
				name: 'id_kategori',
				placeholder: 'Pilih kategori...'
			},
			{
				name: 'id_campaign',
				placeholder: 'Pilih campaign...'
			},
		]
		input_select2.map((item) => {
			var target = `select[name="${item.name}"]`
			$(target).select2({
				placeholder: item.placeholder,
				allowClear: true
			})
		})
	}

	function cainable_form_add() {
		$('select[name="id_donation_type"]').on('select2:select', function(e) {
			var data = e.params.data;
			var id_donation_type = data.id
			var id_kategori = $('select[name="id_kategori"] :selected').val()
			if (id_kategori && id_donation_type) {
				$.post(`${site_url}/donation/chainable_add_donasi`, {
					id_kategori: id_kategori,
					id_donation_type: id_donation_type
				}, function(response) {
					var response = JSON.parse(response)
					if (response.status == "success") {
						set_select2('id_campaign', response.data.campaigns, response.data.campaign.id)
					} else {
						clear_select2('id_campaign')
					}
				})
			}
		})
		$('select[name="id_kategori"]').on('select2:select', function(e) {
			var data = e.params.data;
			var id_donation_type = $('select[name="id_donation_type"] :selected').val()
			var id_kategori = data.id
			if (id_kategori && id_donation_type) {
				$.post(`${site_url}/donation/chainable_add_donasi`, {
					id_kategori: id_kategori,
					id_donation_type: id_donation_type
				}, function(response) {
					var response = JSON.parse(response)
					if (response.status == "success") {
						set_select2('id_campaign', response.data.campaigns, response.data.campaign.id)
					} else {
						clear_select2('id_campaign')
					}
				})
			}
		})
	}

	function set_select2(target, data, selected) {
		var select = `select[name="${target}"]`
		$(select).html('');
		data.map((item) => {
			var option = `<option value="${item.id}">${!item.name ? item.campaign : item.name}</option>`
			$(select).append(option)
		})
		$(select).val(selected);
		$(select).select2().trigger('change');
		$(select).select2({
			placeholder: 'Pilih campaign...',
			allowClear: true
		})
	}

	function clear_select2(target) {
		var select = `select[name="${target}"]`
		$(select).html('');
		$(select).select2({
			placeholder: 'Pilih campaign...',
			allowClear: true
		})
	}

	function save_donation() {
		var data = new FormData();

		var form_data = $("#form-add").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var file_data = $('#attachment').prop('files')[0];
		if (typeof file_data != "undefined") {
			data.append('attachment', file_data);
		}

		$('button').attr('disabled', true)

		$.ajax({
			url: `${site_url}/donation/save`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
				$('button').removeAttr('disabled')
				table_detail_donation.ajax.reload();
			},
			error: function() {
				alertError('Something wrong!')
				$('button').removeAttr('disabled')
			}
		})
	}

	function edit_donation(id) {
		$.post(`${site_url}/donation/edit/json`, {
			'id': id
		}, function(result) {
			var response = JSON.parse(result)
			var nama = response.data.first_name
			Modal.create(`Edit Donasi: ${nama}`, `update_donation`, 'modal-lg', null, 'Update')
			$.post(`${site_url}/donation/edit`, {
				'id': id
			}, function(result) {
				Modal.html(result)
				set_form_add()
				cainable_form_add()
			})
		})
	}

	function btn_delete(id) {
		$.post(`${site_url}/donation/get_delete`, {
			id: id
		}, function(response) {
			var response = JSON.parse(response)
			if (response.status == 'success') {
				swal({
					confirmButtonColor: '#d33',
					title: "Apa kamu yakin?",
					text: `Hapus ${title} ${response.data.campaign} total ${response.data.total}!`,
					type: "warning",
					showCancelButton: true,
					cancelButtonText: 'Batal',
					confirmButtonText: 'Hapus',
					reverseButtons: true,
					buttons: {
						cancel: true,
						confirm: true,
					}
				}, function(result) {
					if (result == true) {
						$.post(`${site_url}/donation/delete`, {
							id: id
						}, function(response) {
							var response = JSON.parse(response)
							if (response.status == 'success') {
								alertSuccess(response.message)
								table_detail_donation.ajax.reload()
							} else {
								alertError(response.message);
							}
						})
					}
				})
			} else {
				alertError(response.message);
			}
		})
	}

	function to_edit() {
		var edit = $('#detail-kontak').data('edit')
		$.post(`${modul_url}/edit`, {
			'id': edit.id
		}, function(result) {
			$('#detail-kontak').html('')
			$('#detail-kontak').append(result)
			var btn_update = `<button class="btn btn-primary btn-lg float-right" onclick="update()">Simpan</button>`
			$('#detail-kontak').append(btn_update)

			// change tombol
			var btn_detail = `<i class="fa fa-eye fa-lg" style="color: #847ae4; padding:10px; cursor:pointer" onclick="to_detail()"></i>`
			$('#toggle-detail-kontak').html('')
			$('#toggle-detail-kontak').append(btn_detail)

			// select2
			$('.select2').select2();

			// handle select province
			$('select[name="id_province"]').on('change', function() {
				var id_province = $(this).val()
				$.ajax({
					url: `${modul_url}/get_city`,
					method: 'post',
					dataType: "json",
					data: {
						id_province: id_province
					},
					success: function(response) {
						if (response.status == 'success') {
							$('select[name="id_city"]').html('')
							response.data.map((item) => {
								var option = `<option value="${item.id}">${item.name}</option>`
								$('select[name="id_city"]').append(option)
							})
						} else {
							alertError(response.message)
						}
					},
					error: function() {
						alertError("Something error!")
					}
				})
			})

			// handle phone flag
			handle_phone_flag()
		})
	}

	function to_detail() {
		var edit = $('#detail-kontak').data('edit')
		$.post(`${modul_url}/ajax_detail`, {
			'id': edit.id
		}, function(result) {
			$('#detail-kontak').html('')
			$('#detail-kontak').append(result)

			// change tombol
			var btn_detail = `<i class="fa fa-edit fa-lg" style="color: #847ae4; padding:10px; cursor:pointer" onclick="to_edit()"></i>`
			$('#toggle-detail-kontak').html('')
			$('#toggle-detail-kontak').append(btn_detail)

			// change tombol
			var btn_detail = `<i class="fa fa-edit fa-lg" style="color: #847ae4; padding:10px; cursor:pointer" onclick="to_edit()"></i>`
			$('#toggle-detail-kontak').html('')
			$('#toggle-detail-kontak').append(btn_detail)

		})
	}

	function update() {
		var data = new FormData();

		//Form data
		var form_data = $("#form-edit").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		// var file_data = $('#attachments').prop('files')[0];
		// if (typeof file_data != "undefined") {
		// 	data.append('attachments', file_data);
		// }

		$('button').attr('disabled', true)

		$.ajax({
			url: `${modul_url}/update`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				var respon = handleResponse(response, false)
				if (respon) {
					to_detail();
				}
				load_activity(1)
			}
		})
	}

	function update_donation() {
		var data = new FormData();

		var form_data = $("#form-edit-donation").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var file_data = $('#attachment').prop('files')[0];
		if (typeof file_data != "undefined") {
			data.append('attachment', file_data);
		}

		$('button').attr('disabled', true)

		$.ajax({
			url: `${site_url}/donation/update`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				var respon = handleResponse(response, false)
				if (respon == true) {
					$('button').removeAttr('disabled')
					table_detail_donation.ajax.reload();
				}
			}
		})
	}

	function send_whatsapp(id) {
		Modal.create('Kirim pesan whatsapp', 'save_send_whatsapp')
		$.post(`${modul_url}/send_whatsapp`, {
			id: id
		}, function(result) {
			Modal.html(result)
		})
	}

	function send_whatsapp_donation(id) {
		Modal.create('Kirim pesan whatsapp', 'save_send_whatsapp_donation')
		$.post(`${site_url}/donation/send_whatsapp`, {
			id: id
		}, function(result) {
			Modal.html(result)
		})
	}

	function save_send_whatsapp() {
		var data = new FormData();

		//Form data
		var form_data = $("#form-send-whatsapp").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var files = $("input[name='attactment[]']");
		for (var i = 0; i < files.length; i++) {
			var file_upload = files.get(i).files
			if (file_upload[0]) {
				data.append("attachments" + i, file_upload[0]);
			}
		}

		$('button').attr('disabled', true)

		$.ajax({
			url: `${modul_url}/save_send_whatsapp`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
				load_activity(1)
			}
		})
	}

	function save_send_whatsapp_donation() {
		var data = new FormData();

		//Form data
		var form_data = $("#form-send-whatsapp").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var files = $("input[name='attactment[]']");
		for (var i = 0; i < files.length; i++) {
			var file_upload = files.get(i).files
			if (file_upload[0]) {
				data.append("attachments" + i, file_upload[0]);
			}
		}

		$('button').attr('disabled', true)

		$.ajax({
			url: `${site_url}/donation/do_send_whatsapp`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
				load_activity(1)
			}
		})
	}

	function send_email(id) {
		Modal.create('Kirim email', 'save_send_email')
		$.post(`${modul_url}/send_email`, {
			id: id
		}, function(result) {
			Modal.html(result)
			$('.content').richText();
		})
	}

	function send_email_donation(id) {
		Modal.create('Kirim email', 'save_send_email_donation')
		$.post(`${site_url}/donation/send_email`, {
			id: id
		}, function(result) {
			Modal.html(result)
			$('.content').richText();
		})
	}

	function save_send_email_donation() {
		var data = new FormData();

		//Form data
		var form_data = $("#form-send-email").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var files = $("input[name='attactment[]']");
		for (var i = 0; i < files.length; i++) {
			var file_upload = files.get(i).files
			if (file_upload[0]) {
				data.append("attachments" + i, file_upload[0]);
			}
		}


		$('button').attr('disabled', true)

		$.ajax({
			url: `${site_url}/donation/do_send_email`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
				load_activity(1)
			}
		})
	}

	function save_send_email() {
		var data = new FormData();

		//Form data
		var form_data = $("#form-send-email").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var files = $("input[name='attactment[]']");
		for (var i = 0; i < files.length; i++) {
			var file_upload = files.get(i).files
			if (file_upload[0]) {
				data.append("attachments" + i, file_upload[0]);
			}
		}


		$('button').attr('disabled', true)

		$.ajax({
			url: `${modul_url}/save_send_email`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
				load_activity(1)
			}
		})
	}

	function edit_label(name, id_user) {
		Modal.create(`Edit label: ${name}`, `update_label`, null, null, 'Update')

		$.post(`${modul_url}/edit_label_detail`, {
			'id_user': id_user
		}, function(result) {
			var respon = JSON.parse(result)

			var labels = ''
			respon.data.labels.map((item, index) => {
				labels += `<option value="${item.id}">${item.name}</option>`
			})
			var form_html = `<form id="form-edit-label">
								<input type="hidden" name="id_user" value="${id_user}" />
								<div class="form-group">
									<select class="form-control select2" name="labels[]" style="width: 100%">
									${labels}
									</select>
								</div>
							</form>`
			Modal.html(form_html)
			$('select[name="labels[]"]').select2({
				placeholder: "Semua label",
				multiple: 'multiple',
				allowClear: true,
				width: '100%',
			});
			$('select[name="labels[]"]').val(respon.data.user_label).change();

			handle_unselect2()
		})
	}

	function update_label() {
		var data = new FormData();

		var form_data = $("#form-edit-label").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		$('button').attr('disabled', true)

		$.ajax({
			url: `${modul_url}/update_label_detail`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				var respon = handleResponse(response, false)
				if (respon) {
					set_label_detail(response.data.id_user)
				}
			}
		})
	}

	function set_label_detail(id_user) {
		$.post(`${modul_url}/edit_label_detail`, {
			'id_user': id_user
		}, function(result) {
			var respon = JSON.parse(result)

			var labels = ''
			respon.data.user_label_name.map((item, index) => {
				labels += `<span class="btn btn-sm btn-outline-secondary text-dark mr-1" >${item.name}</span>`
			})
			$('#labels').html('')
			$('#labels').append(labels)
		})
	}

	function update_catatan() {

		var data = $("#form-catatan").serializeArray();
		$.ajax({
			url: `${modul_url}/update_catatan`,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				var respon = handleResponse(response, false)
			}
		})
	}

	function handle_phone_flag() {
		// handle phone input
		$('#phone').on('keyup input', function() {
			var phone = $(this).val()
			phone = phone.replace(/[^\d]/g, '');

			var first_number = phone.substring(0, 1)

			if (phone.length >= 1) {

				if (first_number == 0) {

					phone = `62${phone.substring(1)}`
				}

				$(this).val(phone)
			}


			if (phone.length >= 2) {
				console.log('LOG : change input')
				var phone_code = phone.substring(0, 2)
				$.post(`${modul_url}/get_country`, {
					'phone_code': phone_code
				}, function(result) {
					var respon = JSON.parse(result)
					if (respon.data != null && respon.data.iso != undefined) {
						var iso = respon.data.iso
						var iso_lower = iso.toLowerCase()
						$('#select2_flag').val(iso_lower).trigger('change');
					}
				})

			}
		})

		// handle change country
		$('#select2_flag').on("select2:select", function() {

			var iso = $(this).val()

			$.post(`${modul_url}/get_country`, {
				'iso': iso
			}, function(result) {
				var respon = JSON.parse(result)
				if (respon.data != null) {
					var phone_code = respon.data.phone_code
					$('#phone').val(phone_code)
				}
			})

		});

		// template flag select2
		$("#select2_flag").select2({
			width: '40%',
			templateResult: function(value) {
				var id = value.id
				if (value.id == undefined) {
					id = 'id'
				}

				var $span = $(`<span><img src='${base_url}/assets/images/flags/${id}.png' width="20"> ${value.text}</span>`)
				return $span;
			},
			templateSelection: function(value) {
				var id = value.id
				if (value.id == undefined) {
					id = 'id'
				}

				var $span = $(`<span><img src='${base_url}/assets/images/flags/${id}.png' width="20"></span>`)
				return $span;
			}
		});
	}

	// handle enter search on page edit
	$('input[name="search_donation"]').keyup(function(event) {
		if (event.keyCode === 13) {
			table_detail_donation.ajax.reload()
		}
	});

	// handle sorting custom
	table_detail_donation.columns().iterator('column', function(ctx, idx) {
		$(table_detail_donation.column(idx).header()).append('<span class="sort-icon"/>');
	})
</script>