 CONTENTS OF THIS FILE
 =====================

 * Introduction
 * Features
 * Requirements
 * Installation

 INTRODUCTION
 ============

 Monitoring provides a sensor interface where a sensor can monitor a specific
 metric or a process in the Drupal application.

 FEATURES
 ========

 * Integration with core modules
 * * Requirements checks
 * * Watchdog
 * * Cron execution
 * * Content and User activity
 * Sensor interface that can be easily implemented to provide custom sensors.
 * Integration with Munin.
 * Integration with Icinga/Nagios

 REQUIREMENTS
 ============

 * PHP min version 5.3
 * Drupal xautoload module to utilise namespaces

 INSTALLATION
 ============

 * Prior to enabling monitoring_* modules enable the monitoring base module.
   This will secure the base PHP classes are available during the submodules
   installation.

 * Enable and configure the desired sensors.


 SENSORS
 =========

 Sensor message
 -------------

 @todo Move general topics, only keep message specific parts (like API
   examples)

 There are two general modes how a sensor message is built.

 1) The sensor can set a message using $result->setSensorMessage(). The message
    will then be displayed as-is.

 2) If there is no pre-defined sensor message, it is built based on the
    available combination of sensor value, status, expected value,
    threshold configuration and status messages.

      // Set the value of the sensor, will be displayed first in the sensor
      // message.
      $result->setSensorValue(5);

      // Set the expected value, will result in critical status if it does not
      // match the value.
      $result->setSensorExpectedValue(10);

      // Adds a sensor status message. Any number of those can be set.
      $result->addSensorStatusMessage('Everything seems fine.');

    The first element that is added is the sensor message is the sensor value,
    if given: "Value 5".

    If there is no value and no status messages, "No value" is used instead.

    Then, if there is an expected value, "expected 10" is added, and if there is
    threshold, a message for the given treshold is added, for example "outside
    the allowed interval 10 - 50".

    Lastly, all status messages are added, resulting in a message like "Value 5,
    expected 10, an explanation as a status message".

 Sensor Info overrides
 --------------

 It is possible to override sensors settings defined in
 hook_monitoring_sensor_info() using the monitoring_sensor variable, for example
 in settings.php. This allows to enforce environment specific settings, like
 disabling a certain sensor.

 $conf['monitoring_sensor_info']['name_of_the_sensor']['settings']['enabled'] = FALSE;

 Anything defined through the hook can be overridden.
