<?php

/**
 * Define system enumerations.
 */
if (!defined('GRINDS_APP')) exit;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Trash = 'trash';
    case Reserved = 'reserved';
    case Private = 'private';
}
