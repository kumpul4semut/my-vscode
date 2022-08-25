@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Tambah product</div>

                <div class="card-body">
                    <form action="/product/doAdd" method="post">
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name">
                        </div>
                        <div class="form-group">
                            <label for="Price">Harga</label>
                            <input type="number" class="form-control" name="price">
                        </div>
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <input type="text" class="form-control" name="name">
                        </div>
                        <div class="form-group">
                            <label for="stock">Stok</label>
                            <input type="text" class="form-control" name="stock">
                        </div>
                        <div class="form-group my-3">
                            <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection
