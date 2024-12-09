<?php

namespace App\Http\Common;

class Constant
{
    public $SUPER_ADMIN_ROLE_ID = 1;
    public $USER_ROLE_ID = 2;
    public $ARTIST_ROLE_ID = 3;
    public $EDITOR_ROLE_ID = 4;
    public $AUTHOR_ROLE_ID = 5;

    public $TOTAL_FAMILY_MEMBERS_LIMIT = 5;

    public $APPOINTMENT_STATUS_PENDING = "pending";
    public $APPOINTMENT_STATUS_PROCESSING = "processing";
    public $APPOINTMENT_STATUS_COMPLETED = "completed";
    public $APPOINTMENT_STATUS_CANCELLED = "cancelled";
    public $APPOINTMENT_STATUS_CANCELLED_BY_USER = "cancelled by user";
    public $APPOINTMENT_STATUS_CANCELLED_BY_DOCTOR = "cancelled by doctor";

    public $PRESCRIPTION_ELEMENT_MEDICINE = 1;
    public $PRESCRIPTION_ELEMENT_LAB_TEST = 2;

    public $STORAGE_DISK_TYPE = "local";
    public $FILE_UPLOAD_PATH = "public/user";
    public const FILE_UPLOAD_PATH = "storage/user";

    public const TOPIC_WIDGET = 1;
    public const RESOURCE_WIDGET = 2;
    public const BANNER_WIDGET = 3;
    public const HEADING_AND_DESCRIPTION_WIDGET = 2;
    public const TOPIC_WIDGET_KEY = 'topics';
    public const BANNER_WIDGET_KEY = 'banners';
    public const RESOURCE_WIDGET_KEY = 'resources';
    public const HEADING_AND_DESCRIPTION_KEY = 'heading_and_description';
    public const MEDICINE_ID = 1;
    
    public const GENERAL_PHYSICIAN_SPECIALITY_ID = 13;
    public const HEALTH_SCAN_HIGH = 'high';

    public const WIDGET_BANNER = "banner";
    public const WIDGET_CARD = "card";
    public const WIDGET_ARTICLE = "article";
    public const WIDGET_DOCTOR = "doctor";
    public const WIDGET_SPECIALITY = "speciality";
    public const WIDGET_REFERENCES = "references";
    public const WIDGET_HEADING_AND_DESCRIPTION = "heading-and-description";
    public const WIDGET_CALL_TO_ACTION = "call-to-action";
    public const WIDGET_DISEASE = "disease";
    public const WIDGET_TOPIC_PILLS = "topic-pills";
    public const WIDGET_VITAL_HEALTH_SCAN = "vital-health-scan";
    public const WIDGET_SEARCH = "search";
    public const WIDGET_CALL_BY_REFERENCE_CARD = "call-by-reference-card";
    public const WIDGET_FAQ = "faq-category";
    public const WIDGET_MEDIA = "media";
    public const WIDGET_MOST_SEARCHED_SPECIALITIES = "most-searched-specialties";
    public const WIDGET_IN_FEED_ARTICLE = "in-feed-article";
    
    public const CACHE_SIGNATURE = "cache_signature";

    public const PAYMENT_METHOD_PAY_CASH_IN_CLINIC = "pay_cash_in_clinic";
    public const PAYMENT_METHOD_CREDIT_DEBIT_CARD = "credit_debit_card";
    public const PAYMENT_METHOD_EASYPAISA_MOBILE_WALLET = "easypaisa_mobile_wallet";
    public const PAYMENT_METHOD_PAY_BANK_TRANSFER = "bank_transfer";
    public const RESTRICT_APPOINTMENT = "restrict_appointment";

    public const APPOINTMENT_TYPE_IN_PERSON = "in-person";
    public const APPOINTMENT_TYPE_INSTANT = "instant-consultation";
    public const APPOINTMENT_TYPE_SCHEDULE = "schedule";

    public const WIDGET_TYPE_PAGE = 1;
    public const WIDGET_TYPE_ARTICLE = 2;
    public const WIDGET_TYPE_BOTH = 3;

    public const MEDICAL_FILE_TYPE_MEDICINE = 1;
    public const MEDICAL_FILE_TYPE_LAB_TEST = 2;
    public const MEDICAL_FILE_TYPE_PRESCRIPTION = 3;


    public static  $numberWords = array('zero','Once','Twice','Thrice');
    public static  $numberWordsUrdu = array('zero','Once','Twice','Thrice');

}