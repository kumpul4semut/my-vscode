<!-- moment js -->
<script src="<?php echo base_url('assets/js/') ?>moment.min.js"></script>
<!-- WYSIWYG Editor js -->
<script src="<?php echo base_url() ?>/assets/plugins/jquery.richtext/jquery.richtext.js"></script>
<script>
	var title = '<?= $title ?>';
	var render_number = $.fn.dataTable.render.number('.', ',', 0).display;
	var table = $('#table-donasi').DataTable({
		ajax: {
			url: `${modul_url}/view_data`,
			type: 'post',
			data: function(d) {
				var filter = sessionStorage.getItem('filter-donasi')
				var data_filter = JSON.parse(filter)

				d.search = $('input[name="search"]').val()
				if (filter) {
					d.jumlah_donasi = data_filter.jumlah_donasi
					d.daterange_trx = data_filter.daterange_trx
					d.kanals = data_filter.kanals
					d.types = data_filter.types
					d.kategoris = data_filter.kategoris
					d.campaigns = data_filter.campaigns
				}
			}
		},
		columns: [
			{
				"data": "date_created",
				render: function(data, type, row, meta) {
					const is_today = moment(data).isSame(moment(), 'day');
					return is_today ? moment(data).format('H:mm:ss') : moment(data).format('DD-MM-YYYY HH:II:SS')
				}
			},
			{
				"data": null,
				render: function(data, type, row, meta) {
					return `<a class="link" target="_blank" href="${modul_url}/detail/${data.id}" >${data.nomor_kwitansi} </a>`
				}
			},
			{
				"data": null,
				render: function(data, type, row, meta) {
					return `<a class="link" target="_blank" href="${site_url}/kontak/detail/${data.id_user}" >${data.first_name} </a>`
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
										<a class="dropdown-item" onclick="send_email('${data.id}')"><i class="fa fa-envelope"></i> kirim kwitansi via email</a>
										<a class="dropdown-item" onclick="send_whatsapp('${data.id}')"><i class="fab fa-whatsapp"></i> kirim kwitansi via whatsapp</a>
									</div>
								</div>`
					return action
				},
			}
		],
		columnDefs: [{
			targets: 6,
			orderable: false
		}],
		order: [
			[0, 'desc']
		],
		lengthChange: false,
		searching: false,
		responsive: true,
		serverSide: true,
		language: {
			info: "Total Transaksi Donasi: _TOTAL_",
		}
		//stateSave: true		
		// bServerSide: true,
		// processing: true,
	})

	$("#table-kontak_length:first").hide()

	function toObject(keys, values) {
		var result = {};
		for (var i = 0; i < keys.length; i++)
			result[keys[i]] = values[i];
		return result;
	}

	function add() {
		Modal.create(`Tambah ${title}`)
		$.post(`${modul_url}/add`, function(result) {
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
				$.post(`${modul_url}/chainable_add_donasi`, {
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
				$.post(`${modul_url}/chainable_add_donasi`, {
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

	function edit(id) {
		$.post(`${modul_url}/edit/json`, {
			'id': id
		}, function(result) {
			var response = JSON.parse(result)
			var nama = response.data.first_name
			Modal.create(`Edit Donasi: ${nama}`, `update`, 'modal-lg', null, 'Update')
			$.post(`${modul_url}/edit`, {
				'id': id
			}, function(result) {
				Modal.html(result)
				set_form_add(true)
				cainable_form_add()
			})
		})
	}

	function btn_delete(id) {
		$.post(`${modul_url}/get_delete`, {
			id: id
		}, function(response) {
			var response = JSON.parse(response)
			if (response.status == 'success') {
				swal({
					confirmButtonColor: '#d33',
					title: "Apa kamu yakin?",
					text: `Hapus ${title} total ${response.data.total}!`,
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
						$.post(`${modul_url}/delete`, {
							id: id
						}, function(response) {
							var response = JSON.parse(response)
							if (response.status == 'success') {
								alertSuccess(response.message)
								table.ajax.reload()
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

	function update() {
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
			url: `${modul_url}/update`,
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
					table.ajax.reload();
				}
			}
		})
	}

	function save() {
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
			url: `${modul_url}/save`,
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
					table.ajax.reload();
				}
			},
			error: function() {
				alertError('Something wrong!')
				$('button').removeAttr('disabled')
			}
		})
	}

	function modal_filter() {
		Modal.create2(`<i class="fa fa-filter" style="color: #867BDD;font-size:1em"></i> Filter`, 'set_filter', 'modal-md', 'reset_filter')

		$.post(`${modul_url}/filter`, function(result) {
			Modal.html(result)

			$('input[name="daterange_trx"]').daterangepicker(default_daterange());

			// filter get data
			var filter = sessionStorage.getItem('filter-donasi')
			if (filter) {
				var data_filter = JSON.parse(filter)
				$('select[name="jumlah_donasi"]').val(data_filter.jumlah_donasi).change();
				$('input[name="daterange_trx"]').val(data_filter.daterange_trx).change();
				$('select[name="kanals[]"]').val(data_filter.kanals).change();
				$('select[name="types[]"]').val(data_filter.types).change();
				$('select[name="kategoris[]"]').val(data_filter.kategoris).change();
				$('select[name="campaigns[]"]').val(data_filter.campaigns).change();

				if (data_filter.daterange_trx) {
					var x_daterange_trx = data_filter.daterange_trx.split('-')
					var start = x_daterange_trx[0].trim()
					var end = x_daterange_trx[1].trim()
					$('input[name="daterange_trx"]').daterangepicker(default_daterange(true, start, end));
				}
			}
			set_form_filter()
			handle_unselect2()
		})

	}

	function set_filter() {
		if (typeof(Storage) !== "undefined") {
			var filter = {}

			var jumlah_donasi = $('select[name="jumlah_donasi"] option').filter(':selected').val()
			var daterange_trx = $('input[name="daterange_trx"]').val()
			var kanals = $('select[name="kanals[]"]').val()
			var types = $('select[name="types[]"]').val()
			var kategoris = $('select[name="kategoris[]"]').val()
			var campaigns = $('select[name="campaigns[]"]').val()

			if (jumlah_donasi) {
				Object.assign(filter, {
					jumlah_donasi: jumlah_donasi
				})
			}

			if (daterange_trx) {
				Object.assign(filter, {
					daterange_trx: daterange_trx
				})
			}

			if (kanals) {
				Object.assign(filter, {
					kanals: kanals
				})
			}

			if (types) {
				Object.assign(filter, {
					types: types
				})
			}

			if (kategoris) {
				Object.assign(filter, {
					kategoris: kategoris
				})
			}

			if (campaigns) {
				Object.assign(filter, {
					campaigns: campaigns
				})
			}

			// save to session javascript
			sessionStorage.setItem("filter-donasi", JSON.stringify(filter));
			// get session
			var filter = sessionStorage.getItem('filter-donasi')
			var data_filter = JSON.parse(filter)
			let count_filter = Object.keys(data_filter).length
			$("#count-filter").text(count_filter)
			table.ajax.reload();
			Modal.close()
		} else {
			alertError("Sorry, your browser does not support Web Storage...")
		}
	}

	function reset_filter() {
		sessionStorage.removeItem("filter-donasi");
		$("#count-filter").text(0)
		$.post(`${modul_url}/filter`, function(result) {
			Modal.html(result)
			set_form_filter()
		})
	}

	function set_form_filter() {
		$('select[name="jumlah_donasi"]').select2({
			placeholder: "Semua jumlah donasi",
			allowClear: true,
		});
		$('select[name="kanals[]"]').select2({
			placeholder: "Pilih kanal",
			allowClear: true
		});
		$('select[name="types[]"]').select2({
			placeholder: "Pilih jenis",
			allowClear: true
		});
		$('select[name="kategoris[]"]').select2({
			placeholder: "Pilih kategori",
			allowClear: true
		});
		$('select[name="campaigns[]"]').select2({
			placeholder: "Pilih campaign",
			allowClear: true
		});

		$('input[name="daterange_trx"]').on('apply.daterangepicker', function(ev, picker) {
			$(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
		});

		$('input[name="daterange_trx"]').on('cancel.daterangepicker', function(ev, picker) {
			$('input[name="daterange_trx"]').val('');
		});
	}

	// filter get data set count filter
	var filter = sessionStorage.getItem('filter-donasi')
	if (filter) {
		var data_filter = JSON.parse(filter)
		let count_filter = Object.keys(data_filter).length
		$("#count-filter").text(count_filter)
	}

	// handle enter search
	$('input[name="search"]').keyup(function(event) {
		if (event.keyCode === 13) {
			table.ajax.reload();
		}
	});

	//------------------- handle live search --------------
	$('#nama-donatur').select2({
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
				return data.length > 0 ? {
					results: $.map(data, function(item) {
						return {
							text: item.first_name,
							id: item.id
						}
					})
				} : {
					results: $.map(data, function(item) {
						return {
							text: 'Masukan nama kontak!',
						}
					})
				}
			}
		}),
	})
	$('#kanal').select2()
	$('#jenis-donasi').select2()
	//------------------- end handle live search --------------

	// handle sorting custom
	// table.columns().iterator('column', function(ctx, idx) {
	// 	$(table.column(idx).header()).append('<span class="sort-icon"/>');
	// })

	function send_email(id) {
		Modal.create('Kirim email', 'do_send_email')
		$.post(`${modul_url}/send_email`, {
			id: id
		}, function(result) {
			Modal.html(result)
			$('.content').richText();
		})
	}

	function do_send_email() {
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
			url: `${modul_url}/do_send_email`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
			}
		})
	}

	function send_whatsapp(id) {
		Modal.create('Kirim whatsapp', 'do_send_whatsapp')
		$.post(`${modul_url}/send_whatsapp`, {
			id: id
		}, function(result) {
			Modal.html(result)
			$('.content').richText();
		})
	}

	function do_send_whatsapp() {
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
			url: `${modul_url}/do_send_whatsapp`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
			}
		})
	}

	// fix dropdown in table responsive
	$('.table-responsive').on('show.bs.dropdown', function() {
		$('.table-responsive').css("overflow", "inherit");
	});

	$('.table-responsive').on('hide.bs.dropdown', function() {
		$('.table-responsive').css("overflow", "auto");
	})
	function import_donation() {
		Modal.create(`Import ${title}`, 'save_import_donation', null);
		$.post(`${modul_url}/import`, function(result) {
			Modal.html(result)
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
			cainable_form_add();
			handle_unselect2()
		})
	}
	
	function save_import_donation() {
		var data = new FormData();

		var form_data = $("#form-import").serializeArray();

		$.each(form_data, function(key, input) {
			data.append(input.name, input.value);
		});

		var file_data = $('#file').prop('files')[0];
		if (typeof file_data != "undefined") {
			data.append('file', file_data);
		}

		$('button').attr('disabled', true)

		$.ajax({
			url: `${modul_url}/save_import`,
			cache: false,
			contentType: false,
			processData: false,
			type: 'post',
			data: data,
			dataType: "json",
			success: function(response) {
				handleResponse(response, false)
			}
		})
	}
	
</script>