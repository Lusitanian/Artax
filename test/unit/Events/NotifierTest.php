<?php

use Artax\Events\Notifier,
    Artax\Injection\Provider,
    Artax\Injection\ReflectionPool;

class NotifierTest extends PHPUnit_Framework_TestCase {

    public function testBeginsEmpty() {
        $m = new Notifier(new Provider(new ReflectionPool));
        return $m;
    }
    
    /**
     * @covers Artax\Events\Notifier::push
     * @covers Artax\Events\Notifier::isValidListener
     * @expectedException InvalidArgumentException
     */
    public function testPushThrowsExceptionOnInvalidListener() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $listeners = $m->push('test.event1', new StdClass);
    }
    
    /**
     * @covers Artax\Events\Notifier::push
     * @covers Artax\Events\Notifier::isValidListener
     * @covers Artax\Events\Notifier::last
     */
    public function testPushAddsEventListenerAndReturnsCount() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 1; };
        $f2 = function(){ return 2; };
        
        $this->assertEquals(1, $m->push('test_event', $f1));
        $this->assertEquals(2, $m->push('test_event', $f2));
    }
    
    /**
     * @covers Artax\Events\Notifier::push
     * @covers Artax\Events\Notifier::setLastQueueDelta
     * @covers Artax\Events\Notifier::getLastQueueDelta
     */
    public function testPushNotifiesDeltaListenersWhenInvoked() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $m->push('test_event', $f1);
        $this->assertEquals(1, $m->getBroadcastCount('__mediator.delta'));
        
        $f2 = function(){ return 'b'; };
        $m->push('test_event', $f2);
        $this->assertEquals(2, $m->getBroadcastCount('__mediator.delta'));
        $this->assertEquals(array('test_event', 'push'), $m->getLastQueueDelta());
    }
    
    /**
     * @covers Artax\Events\Notifier::push
     */
    public function testPushRecursesOnIterableListenerParameter() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $f2 = function(){ return 'b'; };
        $m->push('test_event', array($f1, $f2));
        $this->assertEquals(2, $m->count('test_event'));
    }
  
    /**
     * @covers Artax\Events\Notifier::pushAll
     * @expectedException InvalidArgumentException
     */
    public function testPushAllThrowsExceptionOnNonTraversableParameter() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $m->pushAll('not traversable');
    }
    
    /**
     * @covers Artax\Events\Notifier::pushAll
     */
    public function testPushAllAddsNestedListenersFromTraversableParameter() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $cnt = $m->pushAll(array(
            'app.ready' => function(){},
            'app.event' => array(function(){}, function(){}, function(){})
        ));
        $this->assertNull($cnt);
        $this->assertEquals(1, $m->count('app.ready'));
        $this->assertEquals(3, $m->count('app.event'));
    }
    
    /**
     * @covers Artax\Events\Notifier::unshift
     * @covers Artax\Events\Notifier::first
     */
    public function testUnshiftAddsEventListenerAndReturnsCount() {
        $dp = new Provider(new ReflectionPool);
        $m  = new Notifier($dp);
        $listeners = $m->push('test.event1', function() { return TRUE; });
        $this->assertEquals(1, $listeners);
        
        $listeners = $m->unshift('test.event1', function() { return 42; });
        $this->assertEquals(2, $listeners);
        $this->assertEquals(function() { return 42; }, $m->first('test.event1'));
        return $m;
    }
    
    /**
     * @covers Artax\Events\Notifier::unshift
     * @expectedException InvalidArgumentException
     */
    public function testUnshiftThrowsExceptionOnUncallableNonStringListener() {
        $dp = new Provider(new ReflectionPool);
        $m  = new Notifier($dp);
        $listeners = $m->unshift('test.event1', 1);
    }
    
    /**
     * @covers Artax\Events\Notifier::unshift
     * @covers Artax\Events\Notifier::setLastQueueDelta
     * @covers Artax\Events\Notifier::getLastQueueDelta
     */
    public function testUnshiftNotifiesDeltaListenersWhenInvoked() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $m->unshift('test_event', $f1);
        $this->assertEquals(1, $m->getBroadcastCount('__mediator.delta'));
        
        $f2 = function(){ return 'b'; };
        $m->unshift('test_event', $f2);
        $this->assertEquals(2, $m->getBroadcastCount('__mediator.delta'));
        $this->assertEquals(array('test_event', 'unshift'), $m->getLastQueueDelta());
    }
    
    /**
     * @covers Artax\Events\Notifier::first
     */
    public function testFirstReturnsNullIfNoListenersInQueueForSpecifiedEvent() {
        $dp = new Provider(new ReflectionPool);
        $m  = new Notifier($dp);
        $this->assertEquals(null, $m->first('test.event1'));
    }
    
    /**
     * @covers Artax\Events\Notifier::last
     */
    public function testLastReturnsNullIfNoListenersInQueueForSpecifiedEvent() {
        $dp = new Provider(new ReflectionPool);
        $m  = new Notifier($dp);
        $this->assertEquals(null, $m->last('test.event1'));
    }
    
    /**
     * @covers  Artax\Events\Notifier::count
     */
    public function testCountReturnsNumberOfListenersForSpecifiedEvent() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $f1 = function(){ return 1; };
        $f2 = function(){ return 2; };
        $m->push('test.event1', $f1);
        $m->push('test.event1', $f2);
        
        $this->assertEquals(2, $m->count('test.event1'));
    }
    
    /**
     * @covers  Artax\Events\Notifier::keys
     */
    public function testKeysReturnsArrayOfListenedForEvents() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $m->push('test.event1', function() { return 42; });
        $m->push('test.event2', function() { return 42; });
        $this->assertEquals(array('test.event1', 'test.event2'), $m->keys());
        
        return $m;
    }
    
    /**
     * @covers  Artax\Events\Notifier::clear
     */
    public function testClearRemovesAllListenersForSpecifiedEvent() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $m->push('test.event1', function() { return 42; });
        $m->push('test.event2', function() { return 42; });
        
        $this->assertEquals(array('test.event1', 'test.event2'), $m->keys());
        $m->clear('test.event2');
        $this->assertEquals(array('test.event1'), $m->keys());
    }
    
    /**
     * @covers Artax\Events\Notifier::clear
     * @covers Artax\Events\Notifier::setLastQueueDelta
     * @covers Artax\Events\Notifier::getLastQueueDelta
     */
    public function testClearNotifiesDeltaListenersWhenInvoked() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $m->clear('test_event');
        $this->assertEquals(1, $m->getBroadcastCount('__mediator.delta'));
        
        $f2 = function(){ return 'b'; };
        $m->clear('test_event', $f2);
        $this->assertEquals(2, $m->getBroadcastCount('__mediator.delta'));
        $this->assertEquals(array('test_event', 'clear'), $m->getLastQueueDelta());
    }
    
    /**
     * @covers  Artax\Events\Notifier::pop
     */
    public function testPopRemovesLastListenerForSpecifiedEvent() {
        $m  = new Notifier(new Provider(new ReflectionPool));
        $f1 = function(){ return 1; };
        $f2 = function(){ return 2; };
        $m->push('test.event1', $f1);
        $m->push('test.event1', $f2);
        $popped = $m->pop('test.event1');
        $this->assertEquals($f2, $popped);
        $this->assertEquals(1, $m->count('test.event1'));
    }
    
    /**
     * @covers Artax\Events\Notifier::pop
     * @covers Artax\Events\Notifier::setLastQueueDelta
     * @covers Artax\Events\Notifier::getLastQueueDelta
     */
    public function testPopNotifiesDeltaListenersWhenInvoked() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $m->pop('test_event');
        $this->assertEquals(1, $m->getBroadcastCount('__mediator.delta'));
        
        $f2 = function(){ return 'b'; };
        $m->pop('test_event', $f2);
        $this->assertEquals(2, $m->getBroadcastCount('__mediator.delta'));
        $this->assertEquals(array('test_event', 'pop'), $m->getLastQueueDelta());
    }
    
    /**
     * @depends testKeysReturnsArrayOfListenedForEvents
     * @covers  Artax\Events\Notifier::pop
     */
    public function testPopReturnsNullIfNoEventsMatchSpecifiedEvent($m) {
        $listener = $m->pop('test.eventDoesntExist');
        $this->assertEquals(null, $listener);
    }
    
    /**
     * @covers  Artax\Events\Notifier::shift
     */
    public function testShiftRemovesFirstListenerForSpecifiedEvent() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $f1 = function(){ return 1; };
        $f2 = function(){ return 2; };
        $m->push('test.event1', $f1);
        $m->push('test.event1', $f2);
        $listener = $m->shift('test.event1');
        $this->assertEquals($f1, $listener);
        $this->assertEquals(1, $m->count('test.event1'));
    }
    
    /**
     * @covers Artax\Events\Notifier::shift
     * @covers Artax\Events\Notifier::setLastQueueDelta
     * @covers Artax\Events\Notifier::getLastQueueDelta
     */
    public function testShiftNotifiesDeltaListenersWhenInvoked() {
        $m = new Notifier(new Provider(new ReflectionPool));
        
        $f1 = function(){ return 'a'; };
        $m->shift('test_event');
        $this->assertEquals(1, $m->getBroadcastCount('__mediator.delta'));
        
        $f2 = function(){ return 'b'; };
        $m->shift('test_event', $f2);
        $this->assertEquals(2, $m->getBroadcastCount('__mediator.delta'));
        
        $this->assertEquals(array('test_event', 'shift'), $m->getLastQueueDelta());
    }
    
    /**
     * @depends testKeysReturnsArrayOfListenedForEvents
     * @covers  Artax\Events\Notifier::shift
     */
    public function testShiftReturnsNullIfNoEventsMatchSpecifiedEvent($m) {
        $listener = $m->shift('test.eventDoesntExist');
        $this->assertEquals(null, $listener);
    }
    
    /**
     * @covers Artax\Events\Notifier::unshift
     */
    public function testUnshiftCreatesEventQueueIfNotExists() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $f1 = function(){ return 1; };
        $f2 = function(){ return 2; };
        $this->assertEquals(1, $m->push('test.event1', $f1));
        $this->assertEquals(1, $m->unshift('test.event2', $f2));
        $this->assertEquals(array('test.event1', 'test.event2'), $m->keys());
    }
    
    /**
     * @covers Artax\Events\Notifier::notify
     * @covers Artax\Events\Notifier::getCallableListenerFromQueue
     * @covers Artax\Events\Notifier::incrementEventBroadcastCount
     * @covers Artax\Events\Notifier::incrementListenerInvocationCount
     */
    public function testNotifyDistributesMessagesToListeners() {
        $dp = new Provider(new ReflectionPool);
        $m = new Notifier($dp);
        $this->assertEquals(0, $m->notify('no.listeners.event'));
        
        $m->push('test.event1', function() { return TRUE; });
        $this->assertEquals(1, $m->notify('test.event1'));
        
        $m->push('test.event2', function() { return FALSE; });
        $m->push('test.event2', function() { return TRUE; });
        $this->assertEquals(1, $m->notify('test.event2'));
        
        $m->push('multiarg.test', function($arg1, $arg2, $arg3){});
        $this->assertEquals(1, $m->notify('multiarg.test', 1, 2, 3));
    }
    
    /**
     * @covers Artax\Events\Notifier::notify
     * @covers Artax\Events\Notifier::getCallableListenerFromQueue
     * @covers Artax\Events\Notifier::getBroadcastCount
     * @covers Artax\Events\Notifier::getInvocationCount
     * @covers Artax\Events\Notifier::incrementEventBroadcastCount
     * @covers Artax\Events\Notifier::incrementListenerInvocationCount
     */
    public function testNotifyUpdatesInvocationAndNotificationCounts() {
        $dp = new Provider(new ReflectionPool);
        $m = new Notifier($dp);
        $this->assertEquals(0, $m->notify('no.listeners.event'));
        
        $m->push('test.event1', function() { return TRUE; });
        $this->assertEquals(1, $m->notify('test.event1'));
        
        $this->assertEquals(1, $m->getBroadcastCount('test.event1'));
        $this->assertEquals(1, $m->getInvocationCount('test.event1'));
        
        $m->push('test.event1', function() { return TRUE; });
        $m->push('test.event1', function() { return TRUE; });
        $m->push('test.event1', function() { return TRUE; });
        $this->assertEquals(4, $m->notify('test.event1'));
        
        $this->assertEquals(2, $m->getBroadcastCount('test.event1'));
        $this->assertEquals(5, $m->getInvocationCount('test.event1'));
        
        return $m;
    }
    
    /**
     * @covers Artax\Events\Notifier::getBroadcastCount
     */
    public function testCountNotificationsReturnsAggregateCountOnNullEventParam() {
        $dp = new Provider(new ReflectionPool);
        $m = new Notifier($dp);
        
        $this->assertEquals(0, $m->notify('test.event1'));
        $this->assertEquals(0, $m->notify('test.event2'));
        $this->assertEquals(0, $m->notify('test.event3'));
        $this->assertEquals(0, $m->getBroadcastCount('nonexistent.event'));
    }
    
    /**
     * @covers Artax\Events\Notifier::getInvocationCount
     */
    public function testCountInvocationsReturnsAggregateCountOnNullEventParam() {
        $dp = new Provider(new ReflectionPool);
        $m = new Notifier($dp);
        
        $m->push('test.event1', function() { return TRUE; });
        $m->push('test.event1', function() { return TRUE; });
        $m->push('test.event2', function() { return TRUE; });
        
        $this->assertEquals(2, $m->notify('test.event1'));
        $this->assertEquals(1, $m->notify('test.event2'));
        $this->assertEquals(2, $m->notify('test.event1'));
        $this->assertEquals(0, $m->notify('test.event3'));
        $this->assertEquals(0, $m->getInvocationCount('nonexistent.event'));
    }
    
    /**
     * @covers Artax\Events\Notifier::all
     */
    public function testAllReturnsEventSpecificListIfSpecified() {
        $m = new Notifier(new Provider(new ReflectionPool));
        $f = function() { return TRUE; };
        $m->push('test.event1', $f);
        
        $this->assertEquals(array($f), $m->all('test.event1'));
    }
}

class NotifierTestUninvokableClass {}

class NotifierTestDependency {
    public $testProp = 'testVal';
}

class NotifierTestNeedsDep {
    public $testDep;
    public function __construct(NotifierTestDependency $testDep) {
        $this->testDep = $testDep;
    }
    public function __invoke($arg1) {
        echo $arg1;
    }
}
