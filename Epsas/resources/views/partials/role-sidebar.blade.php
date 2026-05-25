@php
    $currentUser = $sharedAuthUser ?? auth()->user();
    $roleSidebar = $currentUser?->hasRole('secretaria') ? 'slideboard.sidebarsec' : 'slideboard.sidebaradmin';
@endphp

@include($roleSidebar)
