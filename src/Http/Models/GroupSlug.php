<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupSlugModelInterface;

class GroupSlug extends BaseModel implements GroupSlugModelInterface
{
    protected $table='group_slugs';
}