# SimpleAB PHP SDK

This is the PHP SDK for SimpleAB, a powerful A/B testing platform by Captchify.

## Installation

You can install the SimpleAB PHP SDK via Composer:

```bash
composer require simpleab/sdk-php
```

## Usage

Here's a basic example of how to use the SimpleAB PHP SDK:

```php
<?php

require_once 'vendor/autoload.php';

use SimpleAB\SDK\SimpleABSDK;
use SimpleAB\SDK\BaseAPIUrls;
use SimpleAB\SDK\Stages;
use SimpleAB\SDK\AggregationTypes;
use SimpleAB\SDK\Segment;

// Initialize the SDK
$apiURL = BaseAPIUrls::CAPTCHIFY_NA;
$apiKey = 'your-api-key';
$sdk = new SimpleABSDK($apiURL, $apiKey);

// Get a treatment for an experiment
$experimentID = 'your-experiment-id';
$stage = Stages::PROD;
$dimension = 'default';
$allocationKey = 'user-123';

$treatment = $sdk->getTreatment($experimentID, $stage, $dimension, $allocationKey);

// Use the treatment in your application
if ($treatment === 'T1') {
    // Show variant 1
} elseif ($treatment === 'T2') {
    // Show variant 2
} else {
    // Show control or default version
}

// Track a metric
$sdk->trackMetric([
    'experimentID' => $experimentID,
    'stage' => $stage,
    'dimension' => $dimension,
    'treatment' => $treatment,
    'metricName' => 'conversion',
    'metricValue' => 1,
    'aggregationType' => AggregationTypes::SUM
]);

// Get a segment for a user
$segment = $sdk->getSegment([
    'ip' => '123.45.67.89',
    'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
]);

// Get a treatment using the segment
$treatmentWithSegment = $sdk->getTreatmentWithSegment($experimentID, $stage, $segment, $allocationKey);

// Track a metric using the segment
$sdk->trackMetricWithSegment([
    'experimentID' => $experimentID,
    'stage' => $stage,
    'segment' => $segment,
    'treatment' => $treatmentWithSegment,
    'metricName' => 'conversion',
    'metricValue' => 1,
    'aggregationType' => AggregationTypes::SUM
]);

// Make sure to flush metrics before your script ends
$sdk->flush();
```

## API Reference

### SimpleABSDK

#### Constructor

```php
public function __construct($apiURL, $apiKey, $experiments = [])
```

- `$apiURL`: The URL of the SimpleAB API. Use `BaseAPIUrls::CAPTCHIFY_NA` for the North America API.
- `$apiKey`: Your SimpleAB API key.
- `$experiments`: (Optional) An array of experiment IDs to preload.

#### getTreatment

```php
public function getTreatment($experimentID, $stage, $dimension, $allocationKey)
```

Gets the treatment for a given experiment, stage, dimension, and allocation key.

- `$experimentID`: The ID of the experiment.
- `$stage`: The stage of the experiment. Use `Stages::PROD` or `Stages::BETA`.
- `$dimension`: The dimension of the experiment.
- `$allocationKey`: The allocation key for the user.

#### trackMetric

```php
public function trackMetric($params)
```

Tracks a metric for an experiment. The `$params` array should include:

- `experimentID`: The ID of the experiment.
- `stage`: The stage of the experiment. Use `Stages::PROD` or `Stages::BETA`.
- `dimension`: The dimension of the experiment.
- `treatment`: The treatment assigned to the user.
- `metricName`: The name of the metric being tracked.
- `metricValue`: The value of the metric.
- `aggregationType`: The type of aggregation for the metric. Use `AggregationTypes::SUM`, `AggregationTypes::AVERAGE`, or `AggregationTypes::PERCENTILE`.

#### getSegment

```php
public function getSegment($options = [])
```

Gets a segment for a user based on their IP address and user agent.

- `$options`: An array containing:
  - `ip`: The IP address of the user.
  - `userAgent`: The user agent string of the user's browser.

Returns a `Segment` object.

#### getTreatmentWithSegment

```php
public function getTreatmentWithSegment($experimentID, $stage, $segment, $allocationKey)
```

Gets the treatment for a given experiment, stage, segment, and allocation key.

- `$experimentID`: The ID of the experiment.
- `$stage`: The stage of the experiment. Use `Stages::PROD` or `Stages::BETA`.
- `$segment`: A `Segment` object obtained from `getSegment()`.
- `$allocationKey`: The allocation key for the user.

#### trackMetricWithSegment

```php
public function trackMetricWithSegment($params)
```

Tracks a metric for an experiment using a segment. The `$params` array should include:

- `experimentID`: The ID of the experiment.
- `stage`: The stage of the experiment. Use `Stages::PROD` or `Stages::BETA`.
- `segment`: A `Segment` object obtained from `getSegment()`.
- `treatment`: The treatment assigned to the user.
- `metricName`: The name of the metric being tracked.
- `metricValue`: The value of the metric.
- `aggregationType`: The type of aggregation for the metric. Use `AggregationTypes::SUM`, `AggregationTypes::AVERAGE`, or `AggregationTypes::PERCENTILE`.

#### flush

```php
public function flush()
```

Flushes all tracked metrics to the SimpleAB API. Call this method before your script ends to ensure all metrics are sent.

### Segment

The `Segment` class represents a user segment based on their location and device type.

```php
class Segment
{
    public $countryCode;
    public $region;
    public $deviceType;

    public function __construct($countryCode = '', $region = '', $deviceType = '')
    {
        $this->countryCode = $countryCode;
        $this->region = $region;
        $this->deviceType = $deviceType;
    }
}
```

## File Structure

The SDK consists of the following files:

- `src/SimpleABSDK.php`: The main SDK class.
- `md5.php`: A fallback MD5 implementation.
- `composer.json`: Composer package definition.
- `LICENSE`: MIT License file.
- `.gitignore`: Git ignore file.
- `README.md`: This file.
- `tests/SimpleABSDKTest.php`: Unit tests for the SDK.

## Development

To set up the project for development:

1. Clone the repository.
2. Run `composer install` to install dependencies.
3. Make your changes in the `src/SimpleABSDK.php` file.
4. Add or modify tests in the `tests` directory.

### Running Tests

To run the unit tests for the SimpleAB PHP SDK, follow these steps:

1. Ensure you have PHPUnit installed. It should be installed automatically when you run `composer install`.

2. From the root directory of the project, run the following command:

   ```
   ./vendor/bin/phpunit tests
   ```

   This will execute all the tests in the `tests` directory.

3. To run a specific test file, you can specify the file name:

   ```
   ./vendor/bin/phpunit tests/SimpleABSDKTest.php
   ```

4. For more detailed output, you can use the `--verbose` flag:

   ```
   ./vendor/bin/phpunit --verbose tests
   ```

Make sure to run the tests after making any changes to the SDK to ensure that everything is working as expected.

## License

This SDK is distributed under the MIT License. See the LICENSE file for more information.

## Support

For support, please contact support@captchify.com or visit our documentation at https://captchify.com.