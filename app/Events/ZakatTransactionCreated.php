<?php

namespace App\Events;

use App\Models\ZakatTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ZakatTransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\ZakatTransaction> */
    public $transactions;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\ZakatTransaction|\App\Models\ZakatTransaction[]|\Illuminate\Database\Eloquent\Collection $transactions
     * @return void
     */
    public function __construct($transactions)
    {
        if ($transactions instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->transactions = $transactions;
        } elseif (is_array($transactions)) {
            $this->transactions = new \Illuminate\Database\Eloquent\Collection($transactions);
        } else {
            $this->transactions = new \Illuminate\Database\Eloquent\Collection([$transactions]);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('public-transactions');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'transaction.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $items = [];
        foreach ($this->transactions as $tx) {
            $items[] = [
                'id' => $tx->id,
                'category' => $tx->category,
                'uang' => (float) ($tx->nominal_uang ?? 0),
                'beras' => (float) ($tx->jumlah_beras_kg ?? 0),
            ];
        }

        return [
            'no_transaksi' => $this->transactions->isNotEmpty() ? $this->transactions->first()->no_transaksi : null,
            'items' => $items,
        ];
    }
}
