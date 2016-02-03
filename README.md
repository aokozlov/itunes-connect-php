# itunes-connect-php
## What is itunes-connect-php
`itunes-connect-php` is an iTunesConnect PHP API implementation for getting iOS apps analytics to your PHP applications or websites. `itunes-connect-php` uses direct XHR requests to iTunesConnect instead of parsing whole HTML pages unlike other parsers do.
The project is inspired by [fastlane/spaceship](https://github.com/fastlane/spaceship).

#Usage
    //Import `itunes-connect-php` to your project
    require_once 'iTunesConnect.php';

    //Init the iTunesConnect class
    $iTC = new iTunesConnect();

    //Get authorization cookies (needed to pass them to requests)
    $cookies = $iTC->getAuthCookies(ITUNES_ACCOUNT, ITUNES_PASS);

    // Get traffic source campaigns data (sessions, pageViewCount, units, sales)
    $app_id = 999999999;
    $startDate = '2016-01-24';
    $endDate = '2016-01-30';
    $data = $iTC->getCampaignsData($app_id, $startDate, $endDate, $cookies);

#### Result format is
    Array
    (
        [size] => 2
        [results] => Array
            (
                [0] => Array
                    (
                        [adamId] => 999999999
                        [startTime] => 2016-01-17T00:00:00Z
                        [endTime] => 2016-01-24T00:00:00Z
                        [data] => Array
                            (
                                [sessions] => Array
                                    (
                                        [previousValue] => 105
                                        [value] => 157
                                        [percentChange] => 0.5
                                    )

                                [pageViewCount] => Array
                                    (
                                        [previousValue] => 48
                                        [value] => 130
                                        [percentChange] => 1.71
                                    )

                                [units] => Array
                                    (
                                        [previousValue] => 18
                                        [value] => 37
                                        [percentChange] => 1.06
                                    )

                                [sales] => Array
                                    (
                                        [previousValue] => 0
                                        [value] => 0
                                        [percentChange] => 0
                                    )

                            )

                        [campaignId] => google-adwords
                    )

                [1] => Array
                    (
                        [adamId] => 999999999
                        [startTime] => 2016-01-17T00:00:00Z
                        [endTime] => 2016-01-24T00:00:00Z
                        [data] => Array
                            (
                                [sessions] => Array
                                    (
                                        [previousValue] => 21
                                        [value] => 77
                                        [percentChange] => 2.67
                                    )

                                [pageViewCount] => Array
                                    (
                                        [previousValue] => 8
                                        [value] => 70
                                        [percentChange] => 7.75
                                    )

                                [units] => Array
                                    (
                                        [previousValue] => 2
                                        [value] => 18
                                        [percentChange] => 8
                                    )

                                [sales] => Array
                                    (
                                        [previousValue] => 0
                                        [value] => 0
                                        [percentChange] => 0
                                    )

                            )

                        [campaignId] => yandex-direct
                    )

            )

    )

`Note:` For some unknown reasons the iTunesConnect returns wrong `[startTime]` and `[endTime]`. They are different from dates has been requested but the result contains data exact for **requested** dates.

## Changes history
*2016-02-03*
Initial release
+ Authorization
+ getCampaignsData: Gets traffic source campaigns
