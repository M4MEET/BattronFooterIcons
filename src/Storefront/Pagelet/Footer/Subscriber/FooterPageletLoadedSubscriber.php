<?php declare(strict_types=1);

namespace Battron\BattronFooterIcons\Storefront\Pagelet\Footer\Subscriber;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Struct\ArrayEntity;

class FooterPageletLoadedSubscriber implements EventSubscriberInterface
{
    private $systemConfigService;
    private $mediaRepository;
    private $loggerInterface;

    private $numberWords = [
        'One', 'Two', 'Three', 'Four', 'Five',
        'Six', 'Seven', 'Eight', 'Nine', 'Ten'
    ];

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $mediaRepository,
        LoggerInterface $loggerInterface
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->mediaRepository = $mediaRepository;
        $this->loggerInterface = $loggerInterface;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'onFooterPageletLoaded',
        ];
    }

    public function onFooterPageletLoaded(FooterPageletLoadedEvent $event): void
    {
        $context = $event->getContext();
        $salesChannelId = $context->getSource()->getSalesChannelId();
        $page = $event->getPagelet();

        $imgArray = [];

        foreach ($this->numberWords as $numberWord) {
            $paymentLogoId = $this->systemConfigService->get('BattronFooterIcons.config.paymentLogo' . $numberWord, $salesChannelId);
            $shippingLogoId = $this->systemConfigService->get('BattronFooterIcons.config.shippingLogo' . $numberWord, $salesChannelId);

            if ($paymentLogoId !== null && (string)trim($paymentLogoId) !== '') {
                if ($this->findMediaById($paymentLogoId, $context) instanceof MediaEntity) {
                    $imgArray['paymentLogo' . $numberWord] = $this->findMediaById($paymentLogoId, $context)->getUrl();
                } else {
                    $logError = "MEDIA NOT FOUND ERROR -> " . "Check image: " . $paymentLogoId . " in FooterPageletLoadedSubscriber.php";
                    $this->loggerInterface->error($logError);
                }
            }

            if ($shippingLogoId !== null && (string)trim($shippingLogoId) !== '') {
                if ($this->findMediaById($shippingLogoId, $context) instanceof MediaEntity) {
                    $imgArray['shippingLogo' . $numberWord] = $this->findMediaById($shippingLogoId, $context)->getUrl();
                } else {
                    $logError = "MEDIA NOT FOUND ERROR -> " . "Check image: " . $shippingLogoId . " in FooterPageletLoadedSubscriber.php";
                    $this->loggerInterface->error($logError);
                }
            }
        }

        $this->systemConfigService->set('BattronFooterIcons.config.imgArray', $imgArray, $salesChannelId);
        $pluginConfig = $this->systemConfigService->get('BattronFooterIcons.config', $salesChannelId);
        $page->addExtension('BattronFooterIcons', new ArrayEntity($pluginConfig));
    }

    private function findMediaById(string $mediaId, Context $context): ?MediaEntity
    {
        $criteria = new Criteria([$mediaId]);
        $criteria->addAssociation('mediaFolder');
        return $this->mediaRepository
            ->search($criteria, $context)
            ->get($mediaId);
    }
}
