<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index ()
    {
        return view('company.pages.products.index');
    }

    public function store ()
    {
        
    }
}
