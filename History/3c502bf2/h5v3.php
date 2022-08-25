<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Home extends CI_Controller
{


    function __construct()
    {
        parent::__construct();
        $user = $this->db->get_where('tb_pelanggan', ['email' => $this->session->email])->row_array();

        $this->data['cart_pend_total'] = $this->db->get_where('tb_keranjang', ['kode_pelanggan' => $user['kode_pelanggan'], 'status_keranjang' => 0])->num_rows();

        $pesanan = $this->db->select('tb_pesanan.kode_pesanan, tb_pelanggan.nama_pelanggan')
            ->from('tb_pesanan')
            ->join('tb_pelanggan', 'tb_pesanan.kode_pelanggan = tb_pelanggan.kode_pelanggan')
            ->where('tb_pesanan.status_pesanan', 1)
            ->where('tb_pesanan.kode_pelanggan', $user['kode_pelanggan'])
            ->get();

        $this->data['jumlah_pesanan_pend'] = $pesanan->num_rows();
    }

    public function index($kode_kategori = null)
    {
        // pagination
        $perpage = 10;
        $offset = $this->uri->segment(3);
        $config['base_url'] = site_url('home/index');
        $config['total_rows'] = $this->db->get('tb_barang')->num_rows();
        $config['per_page'] = $perpage;
        $this->pagination->initialize($config);

        $this->data['title'] = "Tyo Store";
        $this->data['kategori'] = $this->db->get('tb_barang_kat')->result_array();
        if (isset($kode_kategori)) {
            $produks = $this->db->select('tb_barang .*, tb_barang_kat.nama_kategori')
                ->from('tb_barang')
                ->join('tb_barang_kat', 'tb_barang.kode_kategori = tb_barang_kat.kode_kategori', 'left')
                ->where('tb_barang.kode_kategori', $kode_kategori)->get();
        } else {
            $produks = $this->db->select('tb_barang .*, tb_barang_kat.nama_kategori')->from('tb_barang')->join('tb_barang_kat', 'tb_barang.kode_kategori = tb_barang_kat.kode_kategori', 'left')->limit($perpage, $offset)->get();
        }

        // best seller
        $sql = "SELECT kode_barang, SUM(jumlah) AS TotalQuantity
        FROM tb_keranjang
        GROUP BY kode_barang
        ORDER BY SUM(jumlah) DESC
        LIMIT 3";
        $best_seller = $this->db->select('tb_barang.*,  sum(jumlah) AS TotalQuantity')
            ->from('tb_keranjang')
            ->join('tb_barang', 'tb_keranjang.kode_barang = tb_barang.kode_barang')
            ->group_by('kode_barang')
            ->order_by('sum(jumlah)', 'desc')
            ->limit(3)
            ->get();

        $this->data['produks'] = $produks->result_array();
        $this->data['best_seller'] = $best_seller->result_array();
        foreach ($this->data['produks'] as $k => $prdk) {
            if ($prdk['diskon_barang'] > 0) {
                $diskon = ($prdk['diskon_barang'] / 100) * $prdk['harga_jual'];
                $this->data['produks'][$k]['harga_diskon'] = rpToNumber($prdk['harga_jual'] - $diskon);
            }
        }
        foreach ($this->data['best_seller'] as $k => $bs) {
            if ($bs['diskon_barang'] > 0) {
                $diskon = ($bs['diskon_barang'] / 100) * $bs['harga_jual'];
                $this->data['best_seller'][$k]['harga_diskon'] = rpToNumber($bs['harga_jual'] - $diskon);
            }
        }

        $this->template->public_render('public/home', $this->data);
    }

    public function detail($id_barang = null)
    {
        is_login();
        if ($id_barang) {
            $produk_row = $this->db->select('tb_barang .*, tb_barang_kat.nama_kategori')->from('tb_barang')->join('tb_barang_kat', 'tb_barang.kode_kategori = tb_barang_kat.kode_kategori', 'left')->where('id_barang', $id_barang)->get();
            $this->data['produk'] = $produk_row->row_array();
            if ($this->data['produk']['diskon_barang'] > 0) {
                $diskon = ($this->data['produk']['diskon_barang'] / 100) * $this->data['produk']['harga_jual'];
                $this->data['produk']['harga_diskon'] = rpToNumber($this->data['produk']['harga_jual'] - $diskon);
            }
            $this->data['title'] = $this->data['produk']['nama_barang'];
            $this->template->public_render('public/produk_detail', $this->data);
        } else {
            redirect(base_url());
        }
    }

    public function search()
    {
        $keyword = $this->input->post('keyword');
        // pagination
        $perpage = 10;
        $offset = $this->uri->segment(3);
        $config['base_url'] = site_url('home/search');
        $config['total_rows'] = $this->db->get('tb_barang')->num_rows();
        $config['per_page'] = $perpage;
        $this->pagination->initialize($config);

        $this->data['title'] = "Tyo Store";
        $this->data['kategori'] = $this->db->get('tb_barang_kat')->result_array();
        $produks = $this->db->select('tb_barang .*, tb_barang_kat.nama_kategori')->from('tb_barang')->join('tb_barang_kat', 'tb_barang.kode_kategori = tb_barang_kat.kode_kategori', 'left')->like('tb_barang.nama_barang', $keyword)->limit($perpage, $offset)->get();
        $this->data['produks'] = $produks->result_array();

        $this->template->public_render('public/search', $this->data);
    }

    public function kategori($id_barang_kat = null)
    {
        if (isset($id_barang_kat)) {
            $kategori = $this->db->get_where('tb_barang_kat', ['id_kategori' => $id_barang_kat])->row_array();
            $this->index($kategori['kode_kategori']);
        } else {
            redirect('/');
        }
    }

    public function pesanan()
    {
        $this->data['title'] = "Data pesanan";

        $user = $this->db->get_where('tb_pelanggan', ['email' => $this->session->email])->row_array();

        $pesanans = $this->db->select('tb_pelanggan.nama_pelanggan, tb_pesanan.kode_pesanan, tb_pesanan.status_pesanan, tb_pesanan.id_pesanan, tb_ongkir.estimasi, tb_ongkir.harga_ongkir, tb_pembayaran.kode_pembayaran')
            ->from('tb_pesanan')
            ->join('tb_pelanggan', 'tb_pesanan.kode_pelanggan = tb_pelanggan.kode_pelanggan')
            ->join('tb_ongkir', 'tb_pesanan.kode_ongkir = tb_ongkir.kode_ongkir')
            ->join('tb_pembayaran', 'tb_pesanan.kode_pesanan = tb_pembayaran.kode_pesanan')
            ->order_by('tb_pesanan.id_pesanan', 'desc')
            ->where('tb_pesanan.kode_pelanggan', $user['kode_pelanggan'])
            ->where('tb_pesanan.status_pesanan', 1)
            ->get();

        $this->data['pesanans'] = $pesanans->result_array();

        $this->template->public_render('public/pesanan', $this->data);
    }
}
        
    /* End of file  Home.php */
