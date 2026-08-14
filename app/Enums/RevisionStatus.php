<?php

namespace App\Enums;

enum RevisionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
}
