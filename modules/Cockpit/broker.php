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
    'collections.createcollection',
    'collections.updatecollection',
    'collections.removecollection',
    'collections.find.before',
    'collections.find.after',
    'collections.save.before',
    'collections.save.after',
    'collections.remove.before',
    'collections.remove.after',
    'forms.save.before',
    'forms.save.after',
    'singleton.save.after',
    'singleton.remove',
    'singleton.saveData.before',
    'singleton.saveData.after',
    'singleton.getData.after',
    'cockpit.assets.save',
    'cockpit.assets.remove',
    'cockpit.assets.list',
    'cockpit.update.before',
    'cockpit.update.after',
    'cockpit.media.upload',
    'cockpit.media.removefiles',
    'cockpit.media.rename',
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
    $topic = $rk->newTopic((isset($broker['options']['topic']) ? $broker['options']['topic'] : 'cockpit'));

    foreach ($brokerCalls as $call) {
        $this->trigger('cockpit.broker', [&$call]);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $call['data']);
    }
    $rk->flush(1000);
});
