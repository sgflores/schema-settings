<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use SgFlores\SchemaSetting\Traits\ConfigurableTrait;

class TestUser extends Model
{
    use ConfigurableTrait;

    protected $table = 'users';

    protected $fillable = ['name', 'email'];
}

