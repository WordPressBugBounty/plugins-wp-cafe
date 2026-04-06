---

## 🛠 Installation

1. **Require this package via Composer**

Add the following configuration to your `composer.json`:

```json
{
    "require": {
        "themewinter/email-notification-sdk": "dev-main"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/themewinter/email-notification-sdk"
        }
    ]
}
```
If you do not have, composer installed in your plugin, please install using 

```bash
composer init
```

2. **Update Dependencies**

```bash
composer update
composer dump-autoload
```

3. **On composer udate process if you are asked to give token, followings are the steps you can generate token**
    - Go to GitHub: https://github.com
    - Login to your account.
    - Navigate to Settings:
    - Click your profile picture (top right) → Settings
    - Access Developer Settings:
    - Scroll down in the left sidebar → Click Developer settings
    - Personal access tokens → Tokens (classic):
    - Click Personal access tokens, then choose Tokens (classic)
    - Click "Generate new token" → "Generate new token (classic)"
    - Set token details:
        - Note: Give your token a name (e.g., "Git CLI access")
        - Expiration: Choose an expiry time (e.g., 30 days or "No expiration")
        - Scopes: Select the permissions you need, for example:
            - repo (full control of private repositories)
            - workflow (for GitHub Actions)
            - read:org (if needed for organization access)
            - user (for profile info)
    - Click Generate Token
    - Copy the token immediately — it won't be shown again!

## Configuration

1. **In your plugin's main file, add this initialization code. Make sure this code will be executed after all of your scripts enqued successfully**

```php
    if (file_exists(plugin_dir_path( __FILE__ ) . '/vendor/autoload.php')) {
        require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
    }

    if ( class_exists( \ENS\Core\Sdk::class ) ) {
            \ENS\Core\Sdk::get_instance()
                ->setup([
						'plugin_name' => 'Eventin',
						'plugin_slug' => 'eventin',
						'general_prefix' => 'eve',
						'text_domain' => 'eventin',
						'admin_script_handler' => 'etn-dashboard',
						'sub_menu_filter_hook' => 'eventin_menu',
						'sub_menu_details' => [
							'title'      => __( 'Automation', 'eventin' ),
							'capability' => 'manage_options',
							'url'        => 'admin.php?page=' . 'eventin' . '#/automation',
							'position'   => 10,
						],
                ])
                ->init();

            // here in "ens_eve_available_actions" "eve" is the general_prefix you have sent in the package earlier
            add_filter( 'ens_eve_available_actions', function ( $actions ) {
                $actions = [ // Array of all actions, on which you want to send email
                    [
                        "trigger_label"      => "Event Created", // Name of the event
                        "trigger_value"      => "event_created", // Event slug
                        "trigger_data"       => [ // Data you have after the event happened
                            [
                                "label" => "Event Name",
                                "value" => "event_name",
                                "type"  => "string",
                            ],
                            [
                                "label" => "Event Date",
                                "value" => "event_date",
                                "type"  => "date",
                            ],
                            [
                                "label" => "User Email",
                                "value" => "user_email",
                                "type"  => "string",
                            ]
                        ],
			"conditional_dependencies" => [ // Data you have after the event happened
			    [
                               "label" => "Event Title",
                               "value" => "event_title",
			       "type"  => "string",
                            ],
                        ],
                        "delay_dependencies" => [
                            [
                                "label" => "Event Date",
                                "value" => "event_date",
                            ],
                        ],
                        "email_receivers"    => [
                            [
                                "label" => "User Email",
                                "value" => "user_email",
                            ]
                        ],
                    ],
                    [
                        "trigger_label"      => "Event Rescheduled",
                        "trigger_value"      => "event_rescheduled",
                        "trigger_data"       => [ 
                            [
                                "label" => "Event Name",
                                "value" => "event_name",
                                "type"  => "string",
                            ],
                            [
                                "label" => "Event Date",
                                "value" => "event_date",
                                "type"  => "date",
                            ],
                            [
                                "label" => "User Email",
                                "value" => "user_email",
                                "type"  => "string",
                            ]
                        ],
                        "delay_dependencies" => [
                            [
                                "label" => "Event Date",
                                "value" => "before_event_date", // use before_ or after_ prefix according to your needs
                            ],
                        ],
                        "email_receivers"    => [
                            [
                                "label" => "User Email",
                                "value" => "user_email",
                            ]
                        ],
                    ],
                ];

                return $actions;
            } );
        }
```

## What to do when an event will happen

*** Go to the function, where your handling the event  and write folling code*** 

```php
    do_action( 'global_notification_hook', 'event_rescheduled', [
        'user_email'     => 'badhon001@example.com', // user email
        'event_name'     => 'World cup cricket', //event name
        'event_date_timestamp'     => '17023658981', //timestamp of event date, use wp_timezone() function while converting into timestamp
													// here event_date is the key of your delay dependency.
        'event_title'    => 'Test title'
    ] );
```

 


