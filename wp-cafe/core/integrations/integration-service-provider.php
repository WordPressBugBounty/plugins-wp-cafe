<?php

namespace WpCafe\Integrations;
use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Integrations\Fluent_Crm;
use WpCafe\Integrations\Mail_Mint;
use WpCafe\Integrations\Mail_Poet;
use WpCafe\Integrations\Pabbly;
use WpCafe\Integrations\Zapier;
use WpCafe\Integrations\Zoho_Flow;
use WpCafe\Integrations\Controllers\Mailpoet_Controller;

/**
 * Integration_Service_Provider will responsible for all integrations services
 *
 * @package WpCafe/Integrations
 */
class Integration_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Fluent_Crm::class,
        Mail_Mint::class,
        Mail_Poet::class,
        Pabbly::class,
        Zapier::class,
        Zoho_Flow::class,
        Mailpoet_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_integration_services', $this->services );
    }
}

