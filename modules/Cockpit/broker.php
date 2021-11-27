<?php
/**
 * This file is part of the Cockpit project.
 *
 * (c) Artur Heinze - 🅰🅶🅴🅽🆃🅴🅹🅾, http://agentejo.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// make sure curl extension is loaded
if (!function_exists('curl_init')) {
    return;
}

$events = new \ArrayObject([
    'admin.init',
    'cockpit.account.login',
    'cockpit.account.logout',
    'cockpit.api.authenticate',
    'cockpit.api.erroronrequest',
    'cockpit.assets.list',
    'cockpit.assets.remove',
    'cockpit.assets.save',
    'cockpit.bootstrap',
    'cockpit.clearcache',
    'cockpit.export',
    'cockpit.import',
    'cockpit.media.removefiles',
    'cockpit.media.rename',
    'cockpit.media.upload',
    'cockpit.request.error',
    'cockpit.rest.init',
    'cockpit.update.after',
    'cockpit.update.before',
    'shutdown',
]);

$brokerCalls = new ArrayObject([]);
$broker = $app['config']['broker'];

if ($broker['active'] && $broker['address'] && $broker['options']['topic']) {

    foreach ($events as $evt) {

        $app->on($evt, function() use($evt, $broker, $brokerCalls) {

            $data = json_encode([
                'event' => $evt,
                'hook'  => $webhook['name'],
                'backend' => COCKPIT_ADMIN,
                'args' => func_get_args()
            ]);

            $brokerCalls[] = compact('data');

        }, -1000);
    }
}

$app->on('shutdown', function() use($brokerCalls, $broker) {

    if (!count($brokerCalls)) {
        return;
    }

    $conf = new RdKafka\Conf();
    $rk = new RdKafka\Producer($conf);
    $rk->addBrokers($broker['address']);
    $topic = $rk->newTopic($broker['options']['topic'] || 'cockpit');

    foreach ($brokerCalls as $call) {
        $this->trigger('cockpit.broker', [&$call]);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $call['data']);
    }
    $rk->flush(1000);
});
