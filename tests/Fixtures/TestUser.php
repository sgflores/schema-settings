<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use SgFlores\SchemaSetting\Traits\HasSettings;

class TestUser extends Model
{
    use HasSettings;

    protected $table = 'users';

    protected $fillable = ['name', 'email'];
}
