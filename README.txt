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
