<?php

return [

    'flags' => [

        'exact_duplicate' => [
            'label' => 'Potential duplicate transaction',
            'summary' => 'The system found another very similar transaction in close timing.',
            'next_step' => 'Check whether this is a double entry or separate payments.',
        ],

        'transfer_duplicate_candidate' => [
            'label' => 'Transfer duplicate candidate',
            'summary' => 'Another transfer with the same payer and amount occurred in a close time window.',
            'next_step' => 'Confirm the transfer is not a double entry for the same payment.',
        ],

        'payer_match_same_beneficiary' => [
            'label' => 'Same payer and beneficiary',
            'summary' => 'Another transaction with the same payer and beneficiary appeared in a close time window.',
            'next_step' => 'Verify whether this is a separate payment or a re-entry.',
        ],

        'payer_match_different_beneficiary' => [
            'label' => 'Same payer, different beneficiary',
            'summary' => 'Same payer and amount with a different beneficiary within a close time window.',
            'next_step' => 'Confirm the payment is actually intended for the recorded beneficiary.',
        ],

        'updated_after_receipt_printed' => [
            'label' => 'Edited after receipt was printed',
            'summary' => 'Transaction data changed after a printed receipt was issued.',
            'next_step' => 'Confirm the edit is valid and does not cause discrepancies with already-distributed receipts.',
        ],

        'significant_nominal_change' => [
            'label' => 'Significant nominal change',
            'summary' => 'Total cash or rice for the transaction group changed significantly.',
            'next_step' => 'Compare the old and new values, then ensure the change matches the field need.',
        ],

        'statistical_outlier' => [
            'label' => 'Statistical outlier',
            'summary' => 'The transaction amount is far above the usual intake average.',
            'next_step' => 'Verify whether there is a typo (extra zero) or the payer genuinely paid a large amount.',
        ],

        'restored_after_delete' => [
            'label' => 'Restored after delete',
            'summary' => 'This transaction was previously deleted and has now been restored.',
            'next_step' => 'Recheck the payer, beneficiary, and amounts before closing the case.',
        ],

    ],

];