<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleVisits extends Model
{
    use HasFactory;
    protected $fillable = ['load_datetime', 'ip_address', 'user_agent', 'user_details', 'article_id', 'unload_datetime', 'visit_hash'];
}
