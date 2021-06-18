<?php

/**
* Plugin Name: Currency Sync TSM
* Description: Periodically fetch currencies.
* Plugin URI: https://thinksurfmedia.com
* Version: 0.0.1
* Author: Arabinda
* Author URI: https://dzziner.com/
**/

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit; // Exit if accessed directly.
}

if(!class_exists('TSMCurrencies')){
    class TSMCurrencies{

        public function __construct(){
            $this->init();
        }

        public function init(){
            //rest initiate
            add_action( 'rest_api_init', array($this,'initiate_currency_route') );

            //initiate schedule
            add_filter('cron_schedules', array($this, 'add_schedule_time'));
			if (!wp_next_scheduled('tsm_fetch_currency')) {
				$next_timestamp = wp_next_scheduled('tsm_fetch_currency');
				if ($next_timestamp) {
					wp_unschedule_event($next_timestamp, 'tsm_fetch_currency');
				}
				wp_schedule_event(time(), 'daily', 'tsm_fetch_currency');
			}
			add_action('tsm_fetch_currency', array($this, 'fetch_currency'));

            if(empty(get_currencies())){
                $this->fetch_currency();
            }

        }

        //schedule time
		function add_schedule_time($schedules){
			$schedules['daily'] = array(
				'interval' => 24*60 * 60,
				'display'  => esc_attr__('1 days', 'tsm'),
			);
			return $schedules;
		}



        //currency fetch
        function fetch_currency(){
            $url='https://api.exchangeratesapi.io/v1/latest?base=USD&access_key=71347881a31ead459eb4c9af09d0e0b3';
            $args = array(
                'timeout' => 60
            );
            $request = wp_remote_get($url, $args);
            $data = json_decode(wp_remote_retrieve_body( $request ));

            if($data['success'] == true && array_key_exists('rates',$data)){
                return update_currencies($data);
            }
        }


        public function update_currencies($data){
            update_option('tsm_currencies',$data);
        }
        
        public function get_currencies(){
            return get_option('tsm_currencies',true);
        }
        

        public function initiate_currency_route() {
            register_rest_route( 'tsm', 'currency', array(
                            'methods' => 'GET',
                            'callback' => array($this,'currencies'),
                        )
                    );
        }

        public function currencies() {
            return get_currencies();
        }

    }
}
new TSMCurrencies();

?>