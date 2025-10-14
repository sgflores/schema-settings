<?php

namespace SgFlores\SchemaSetting\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use SgFlores\SchemaSetting\Traits\ConfigurableTrait;

class TestTeam extends Model
{
    use ConfigurableTrait;

    protected $table = 'teams';

    protected $fillable = ['name'];
}

