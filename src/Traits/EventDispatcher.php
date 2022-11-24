<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use DigipolisGent\Robo\Helpers\EventHandler\EventHandlerWithPriority;
use DigipolisGent\Robo\Helpers\Util\AddToContainerInterface;
use League\Container\ContainerAwareInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\TaskInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait EventDispatcher {

    /**
     * Handle an event to build a task.
     *
     * @param string $eventName
     *   The name of the event to handle.
     *
     * @param array $arguments
     *   Associative array of arguments.
     *
     * @return CollectionBuilder
     *   The collection of tasks returned by the handlers.
     */
    protected function handleTaskEvent(string $eventName, array $arguments): TaskInterface
    {
        $handlers = $this->getEventHandlers($eventName);

        $event = new GenericEvent();
        $event->setArguments($arguments);
        $collection = $this->collectionBuilder();
        foreach ($handlers as $handler) {
            $collection->addTask($handler->handle($event));
            if ($event->isPropagationStopped()) {
                break;
            }
        }
        return $collection;
    }

    /**
     * Handle an event.
     *
     * @param string $eventName
     *   The name of the event to handle.
     *
     * @param array $arguments
     *   Associative array of arguments.
     *
     * @return array
     *   The results of the event handlers.
     */
    protected function handleEvent(string $eventName, array $arguments): array
    {
        $handlers = $this->getEventHandlers($eventName);

        $event = new GenericEvent();
        $event->setArguments($arguments);
        $result = [];
        foreach ($handlers as $handler) {
            $result[] = $handler->handle($event);
            if ($event->isPropagationStopped()) {
                break;
            }
        }
        return $result;
    }

    /**
     * Returns a sorted (by priority) list of event handlers.
     *
     * @param string $eventName
     *   The name of the event to get the handlers for.
     *
     * @return EventHandlerWithPriority[]
     *   The sorted list of handlers for the given event.
     */
    protected function getEventHandlers(string $eventName): array
    {
        $handlerFactories = $this->getCustomEventHandlers($eventName);
        $handlers = [];

        foreach ($handlerFactories as $handlerFactory) {
            /** @var EventHandlerWithPriority $handler */
            $handler = $handlerFactory();
            // If the handler implements the AddToContainerInterface, add it to
            // the container, so all its dependencies are injected, based on the
            // other interfaces it implements.
            if ($handler instanceof AddToContainerInterface && $this instanceof ContainerAwareInterface) {
                $class = get_class($handler);
                if (!$this->getContainer()->has($class)) {
                    $this->getContainer()->addShared($class, $handler);
                }
                // Inflectors only run when getting the service.
                $handler = $this->getContainer()->get($class);
            }

            // Inject the builder if possible and needed.
            if ($handler instanceof BuilderAwareInterface && $this instanceof BuilderAwareInterface && $this instanceof ContainerAwareInterface) {
                $handler->setBuilder(CollectionBuilder::create($this->getContainer(), $handler));
            }
            $handlers[] = $handler;
        }

        usort($handlers, function (EventHandlerWithPriority $handlerA, EventHandlerWithPriority $handlerB) {
            return $handlerA->getPriority() - $handlerB->getPriority();
        });

        return $handlers;
    }
}
