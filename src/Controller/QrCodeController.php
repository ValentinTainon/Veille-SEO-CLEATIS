<?php

declare(strict_types=1);

namespace App\Controller;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface as TotpTwoFactorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class QrCodeController extends AbstractController
{
    #[Route('/authentication/qr-code/totp', name: 'qr_code_totp')]
    #[IsGranted('ROLE_REDACTEUR')]
    public function displayTotpQrCode(TokenStorageInterface $tokenStorage, TotpAuthenticatorInterface $totpAuthenticatorInterface): Response
    {
        $user = $tokenStorage->getToken()->getUser();

        if (!($user instanceof TotpTwoFactorInterface))
            throw new NotFoundHttpException('Impossible d\'afficher le QR code');

        $qrCode = $this->displayQrCode($totpAuthenticatorInterface->getQRContent($user));

        $qrCodeEncoded = base64_encode($qrCode);
       
        return $this->render('security/qr_code.html.twig', ['qrCode' => $qrCodeEncoded]);
    }

    private function displayQrCode(string $qrCodeContent): String
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(200)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->build();

        return $result->getString();
    }
}