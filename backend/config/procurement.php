<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Originator-skip for the Budget Holder approval stage
    |--------------------------------------------------------------------------
    |
    | Per the ED's official Procurement & Payment Approval Process (v2, §3.4):
    | "if the Programme Manager raises the request themselves, the system
    | auto-advances past the Programme Manager step."
    |
    | When true, a purchase request whose Budget Holder is the same person who
    | raised/submitted it (matched by email) auto-skips the Budget Holder stage
    | on submission and the chain opens at Finance instead. This also enforces
    | segregation of duties at that stage — a requester cannot approve their own
    | request.
    |
    | The ED asked us to keep this configurable. Toggle it via
    | PROCUREMENT_ORIGINATOR_SKIP in the API .env — no code redeploy required.
    |
    */
    'originator_skip' => (bool) env('PROCUREMENT_ORIGINATOR_SKIP', true),

];
