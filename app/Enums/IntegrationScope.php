<?php

namespace App\Enums;

enum IntegrationScope: string
{
    case Blog = 'blog';
    case Author = 'author';
    case Article = 'article';
}
