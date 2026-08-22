<?php

namespace Alle80\Griglia\Domain;

enum ReviewOutcome: string
{
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
}
