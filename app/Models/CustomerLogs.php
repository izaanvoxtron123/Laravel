<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CustomerLogs extends Model
{
    use HasFactory;
    protected $table = 'customer_logs';
    protected $guarded = ['id'];

    // JOURNEY
    public const CUSTOMER_CREATED = 'customer_created'; // USING ****************
    public const REPORT_REQUESTED = 'report_requested';
    public const REPORT_FETCHED = 'report_fetched'; // USING ****************
    public const REPORT_ATTACHED = 'report_attached'; // report_id wil go in supporting_id


    public const CUSTOMER_INFO_UPDATED = 'customer_info_updated';  // changelog will go in payload
    public const CUSTOMER_CARDS_UPDATED = 'customer_cards_updated';  // changelog will go in payload
    public const CUSTOMER_PHONES_UPDATED = 'customer_phones_updated';  // changelog will go in payload
    public const CUSTOMER_ADDRESSES_UPDATED = 'customer_addresses_updated';  // changelog will go in payload


    public const SUBMITTED = 'submitted'; // USING ****************
    public const MARKED_INCOMPLETE = 'marked_incomplete';  // USING ****************
    public const CUSTOMER_COMMENT_UPDATED = 'customer_comment_updated'; // Changelog will go in supporting_text
    public const SALE_STATUS_UPDATED = 'sale_status_updated'; // New status will go in supporting_text
    public const SPECIALIST_ASSIGNED = 'specialist_assigned'; // Specialist_id will go in supporting_id
    public const DOWNLOADED = 'downloaded';

    public const MOVED_TO_DOCS = 'moved_to_docs';

    public const PROGRESS_UPDATED = 'progress_updated'; // USING ****************

    // VIEW ACTIONS
    public const REPORT_VIEWED = 'report_viewed';
    public const PROFILE_VIEWED = 'profile_viewed';
    public const EDIT_INITIATED = 'edit_initiated';

    public const CARD_VIEWED = 'card_viewed';
    public const META_VIEWED = 'meta_viewed';
    public const RECORDING_PLAYED = 'recording_played';




    public static function createLog($customer_id, $type, $supporting_text = null, $supporting_id = null, $payload = null)
    {
        self::create([
            'customer_id' => $customer_id,
            'action_by' => Auth::user()->id,
            'type' => $type,
            'supporting_text' => $supporting_text,
            'supporting_id' => $supporting_id,
            'payload' => $payload,
        ]);
    }

    function actionBy()
    {
        return $this->belongsTo(User::class, "action_by");
    }
}
