<?php

namespace App\Jobs;

use App\Http\Common\FcmHelper;
use App\Models\Fcm_Tokens;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        array $user_ids,
        string $title,
        string $text,
        string $module,
        string $supporting_id,
    ) {
        $this->user_ids = $user_ids;
        $this->title = $title;
        $this->text = $text;
        $this->module = $module;
        $this->supporting_id = $supporting_id;
    }
    public $user_ids;
    public $title;
    public $text;
    public $module;
    public $supporting_id;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $fcm = new FcmHelper;
        $tokens = Fcm_Tokens::whereIn('user_id', $this->user_ids)->pluck('token')->toArray();

        $fcm->push($tokens, $this->title, $this->text, $this->module, $this->supporting_id);
    }
}
