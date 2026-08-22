<?php

namespace Alle80\Griglia\Domain;

enum ReviewStatus: string
{
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
}
