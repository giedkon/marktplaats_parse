<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scrap extends Model
{
    use HasFactory;

    protected $fillable = ['scrap_url', 'title', 'year', 'mileage', 'price', 'make_model', 'fuel', 'body_type', 'views', 'description', 'scraper_ip', 'image_large', 'image_thumb'];
}
