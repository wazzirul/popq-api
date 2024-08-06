<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportFiles extends Model
{
    protected $table = 'import_files';
    protected $primaryKey = 'id_import_files';
    protected $fillable = ['id_import_files','import_files_name','status','soft_delete'];
}
