<?php

return [

    'flags' => [

        'exact_duplicate' => [
            'label' => 'Potential duplicate transaction',
            'summary' => 'The system found another very similar transaction in close timing.',
            'next_step' => 'Check whether this is a double entry or separate payments.',
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

    ],

];