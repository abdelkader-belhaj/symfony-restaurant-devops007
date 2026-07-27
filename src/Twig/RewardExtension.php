<?php

namespace App\Twig;

use App\Service\FirebaseService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RewardExtension extends AbstractExtension
{
    public function __construct(
        private readonly FirebaseService $firebaseService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_unclaimed_rewards', [$this, 'hasUnclaimedRewards']),
        ];
    }

    public function hasUnclaimedRewards(?array $userSession): bool
    {
        if (!$userSession || !isset($userSession['id'])) {
            return false;
        }

        $storedUser = $this->firebaseService->getUser((string) $userSession['id']);
        if (!$storedUser) {
            return false;
        }

        // Si l'utilisateur a déjà une offre active (remise activée) ou a déjà gagné un cadeau à la roulette
        // on n'affiche plus la notification (badge clignotant) car il a déjà "réclamé" ses offres.
        if (($storedUser['discountActive'] ?? false) || !empty($storedUser['rouletteGift'])) {
            return false;
        }

        $summary = $this->firebaseService->getClientRewardSummary($storedUser['nomComplete']);

        // Condition 1: Remise de 25% (> 500$)
        // Disponible si total > 500 ET non activée ET non utilisée
        $canActivateDiscount = ($summary['rewardAvailable'] ?? false)
            && !($storedUser['discountActive'] ?? false)
            && !($storedUser['discountUsed'] ?? false);

        // Condition 2: Plat roulette (> 1000$)
        // Disponible si total > 1000 ET roulette non utilisée ET pas encore de cadeau gagné (rouletteGift est nul)
        $canSpinRoulette = ($summary['rouletteAvailable'] ?? false)
            && !($storedUser['rouletteUsed'] ?? false)
            && empty($storedUser['rouletteGift']);

        return $canActivateDiscount || $canSpinRoulette;
    }
}
