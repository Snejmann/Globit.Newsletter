<?php
namespace Globit\Newsletter\Controller;

/*
 * This file is part of the Globit.Newsletter package.
 */

use Globit\Newsletter\Domain\Model\Newsletter;
use Globit\Newsletter\Domain\Repository\NewsletterRepository;
use Psmb\Newsletter\Domain\Model\Subscriber;
use Psmb\Newsletter\Domain\Repository\SubscriberRepository;
use Psmb\Newsletter\Service\FusionMailService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Flow\Utility\Now;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\View\TypoScriptView;
use TYPO3\TYPO3CR\Domain\Service\Context;

class StandardController extends AbstractModuleController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = TypoScriptView::class;

    /**
     * @Flow\Inject
     * @var FusionMailService
     */
    protected $fusionMailService;

    /**
     * @Flow\InjectConfiguration(package="TYPO3.Flow", path="http.baseUri")
     * @var string
     */
    protected $baseUri;

    /**
     * @Flow\Inject
     * @var SubscriberRepository
     */
    protected $subscriberRepository;

    /**
     * @Flow\Inject
     * @var NewsletterRepository
     */
    protected $newsletterRepository;

    /**
     * @Flow\InjectConfiguration(path="subscriptions", package="Psmb.Newsletter")
     * @var array
     */
    protected $subscriptions;

    /**
     * @return void
     */
    public function indexAction()
    {
        $context = new Context('live', new Now(), [], [], TRUE, FALSE, FALSE);
        $subscriptions = array_map(function ($subscription) {
            return (object)[
                'identifier' => $subscription['identifier'],
                'subscribers' => $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])
            ];
        }, $this->subscriptions);

        $this->view->assign('site', $context->getRootNode());
        $this->view->assign('subscriptions', $subscriptions);
        $this->view->assign('subscribers', $this->subscriberRepository->findAll());
    }

    /**
     * @param NodeInterface $node
     * @param string $mail
     * @param string $subscription
     * @Flow\Validate(argumentName="node", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     * @Flow\Validate(argumentName="mail", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     * @Flow\Validate(argumentName="mail", type="\TYPO3\Flow\Validation\Validator\EmailAddressValidator")
     * @Flow\Validate(argumentName="subscription", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     */
    public function testAction($node, $mail, $subscription)
    {
        $subscription = $this->subscriptions[array_search($subscription, array_column($this->subscriptions, 'identifier'))];

        $dummySubscriber = new Subscriber();
        $dummySubscriber->setName($mail);
        $dummySubscriber->setEmail($mail);

        $letter = $this->fusionMailService->generateSubscriptionLetter($dummySubscriber, $subscription, $node);

        $this->fusionMailService->sendLetter(array_merge($letter, (array)$dummySubscriber));

        $this->addFlashMessage('Newsletter has been sent to "%s"', 'Testmail', Message::SEVERITY_OK, [$mail]);

        $this->redirect('index');
    }

    /**
     * TODO: set flag for chron maybe? -> send generated letter
     *
     * @return void
     * @param NodeInterface $node
     * @param string $subscription
     * @Flow\Validate(argumentName="node", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     * @Flow\Validate(argumentName="subscription", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     */
    public function bulkAction($node, $subscription)
    {
        $subscription = $this->subscriptions[array_search($subscription, array_column($this->subscriptions, 'identifier'))];
        $subscribers = $this->subscriberRepository->findBySubscriptionId($subscription['identifier'])->toArray();

        // for now, just send newsletter
//        foreach ($subscribers as $subscriber) {
//            $this->newsletterRepository->add(new Newsletter($subscriber, $node, $subscription['identifier']));
//        }
//        $this->sendMails();

        $this->addFlashMessage('Service has been started.', 'Newsletter', Message::SEVERITY_OK);

        $letters = array_map(function ($subscriber) use ($subscription, $node) {
            $letter = $this->fusionMailService->generateSubscriptionLetter($subscriber, $subscription, $node);
            if (!$letter) {
                // Error
            }
            return $letter;
        }, $subscribers);

        foreach (array_filter($letters) as $letter) {
            $this->fusionMailService->sendLetter($letter);
        }

        $this->redirect('index');
    }

    /**
     * Useful function for cronjob
     */
    protected function sendMails()
    {
        /** @var Newsletter $newsletter */
        $context = new Context('live', new Now(), [], [], TRUE, FALSE, FALSE);

        foreach ($this->newsletterRepository->findByStatus(Newsletter::STATUS_WAITING) as $newsletter) {
            $letter = $this->fusionMailService->generateSubscriptionLetter(
                $newsletter->getSubscriber(),
                $this->subscriptions[array_search($newsletter->getSubscription(), array_column($this->subscriptions, 'identifier'))],
                $newsletter->getNodePath()
            );

            $this->fusionMailService->sendLetter($letter);

            $newsletter->setStatus(Newsletter::STATUS_SUCCESS);
            $this->newsletterRepository->update($newsletter);
        }
    }

    /**
     * We can't do this in constructor as we need configuration to be injected
     */
    public function initializeObject()
    {
        $request = $this->createRequest();
        $controllerContext = $this->createControllerContext($request);
        $this->fusionMailService->setupObject($controllerContext, $request);
    }

    /**
     * Simply sets the TypoScript path pattern on the view.
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $view->setTypoScriptPathPattern('resource://Globit.Newsletter/Private/TypoScript/Backend/');
    }

    /**
     * @return ActionRequest
     */
    protected function createRequest()
    {
        $_SERVER['FLOW_REWRITEURLS'] = 1;
        $httpRequest = Request::createFromEnvironment();
        if ($this->baseUri) {
            $baseUri = new Uri($this->baseUri);
            $httpRequest->setBaseUri($baseUri);
        }
        $actionRequest = new ActionRequest($httpRequest);
        $actionRequest->setFormat('html');
        return $actionRequest;
    }

    /**
     * Creates a controller content context for live dimension
     *
     * @param ActionRequest $request
     * @return ControllerContext
     */
    protected function createControllerContext($request)
    {
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $controllerContext = new ControllerContext(
            $request,
            new Response(),
            new Arguments([]),
            $uriBuilder
        );
        return $controllerContext;
    }
}
