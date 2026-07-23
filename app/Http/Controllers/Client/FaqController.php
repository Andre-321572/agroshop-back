<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $faqData = [
            [
                'category' => 'Commandes',
                'icon' => 'fas fa-shopping-cart',
                'color' => 'green',
                'questions' => [
                    [
                        'question' => 'Comment passer une commande sur AGROSHOP ?',
                        'answer' => 'Pour passer une commande, parcourez notre catalogue de produits, ajoutez les articles souhaités à votre panier, puis procédez au checkout. Vous devrez renseigner vos informations de contact et choisir votre mode de livraison. Aucun compte n\'est requis pour commander.'
                    ],
                    [
                        'question' => 'Puis-je modifier ou annuler ma commande ?',
                        'answer' => 'Vous pouvez modifier ou annuler votre commande tant qu\'elle n\'a pas été confirmée par nos équipes. Contactez-nous rapidement au +228 96 53 89 30 ou par email à contact@agroshop.tg avec votre numéro de commande.'
                    ],
                    [
                        'question' => 'Comment suivre l\'état de ma commande ?',
                        'answer' => 'Après validation de votre commande, vous recevrez un code de référence. Vous pouvez utiliser ce code pour suivre l\'état de votre commande ou nous contacter directement. Nous vous informerons par SMS ou email de chaque étape.'
                    ],
                    [
                        'question' => 'Quels sont les délais de livraison ?',
                        'answer' => 'Les délais de livraison varient selon votre localisation : 24-48h pour Lomé, 2-3 jours pour les autres villes du Togo. Pour les commandes spéciales ou en grande quantité, comptez 3-5 jours ouvrables.'
                    ]
                ]
            ],
            [
                'category' => 'Produits',
                'icon' => 'fas fa-seedling',
                'color' => 'yellow',
                'questions' => [
                    [
                        'question' => 'Vos produits sont-ils authentiques ?',
                        'answer' => 'Tous nos produits sont authentiques et proviennent directement des fabricants agréés. Nous disposons de tous les certificats nécessaires et respectons les normes phytosanitaires en vigueur au Togo.'
                    ],
                    [
                        'question' => 'Proposez-vous des conseils d\'utilisation ?',
                        'answer' => 'Oui, chaque produit est accompagné de sa fiche technique détaillée. Nos conseillers techniques sont également disponibles pour vous guider dans le choix et l\'utilisation de nos produits selon vos besoins spécifiques.'
                    ],
                    [
                        'question' => 'Comment connaître la disponibilité d\'un produit ?',
                        'answer' => 'La disponibilité est indiquée en temps réel sur chaque fiche produit. Si un produit est en rupture de stock, vous pouvez nous laisser vos coordonnées pour être notifié de son retour en stock.'
                    ],
                    [
                        'question' => 'Proposez-vous des remises sur les gros volumes ?',
                        'answer' => 'Oui, nous proposons des tarifs dégressifs pour les commandes en gros volumes. Contactez notre service commercial pour obtenir un devis personnalisé selon vos quantités.'
                    ]
                ]
            ],
            [
                'category' => 'Livraison',
                'icon' => 'fas fa-truck',
                'color' => 'green',
                'questions' => [
                    [
                        'question' => 'Quels sont vos modes de livraison ?',
                        'answer' => 'Nous proposons la livraison à domicile dans tout le Togo et le retrait en agence dans nos points de vente à Lomé. La livraison à domicile est gratuite pour les commandes supérieures à 50 000 FCFA.'
                    ],
                    [
                        'question' => 'Puis-je choisir ma date de livraison ?',
                        'answer' => 'Oui, lors de votre commande, vous pouvez spécifier une date de livraison souhaitée. Nous ferons notre possible pour respecter cette date selon la disponibilité de nos équipes de livraison.'
                    ],
                    [
                        'question' => 'Que faire si je ne suis pas présent à la livraison ?',
                        'answer' => 'Notre livreur tentera de vous contacter. Si vous n\'êtes pas disponible, vous pouvez désigner une personne de confiance pour réceptionner la commande ou reporter la livraison au lendemain.'
                    ],
                    [
                        'question' => 'Quels sont les frais de livraison ?',
                        'answer' => 'Les frais de livraison sont de 5 000 FCFA pour Lomé et 7 500 FCFA pour les autres villes. La livraison est gratuite pour toute commande supérieure à 50 000 FCFA.'
                    ]
                ]
            ],
            [
                'category' => 'Paiement',
                'icon' => 'fas fa-credit-card',
                'color' => 'yellow',
                'questions' => [
                    [
                        'question' => 'Quels sont les modes de paiement acceptés ?',
                        'answer' => 'Nous acceptons les paiements par Mobile Money (Flooz, T-Money), virement bancaire, et espèces à la livraison. Pour les gros montants, nous recommandons le virement bancaire.'
                    ],
                    [
                        'question' => 'Le paiement à la livraison est-il possible ?',
                        'answer' => 'Oui, vous pouvez payer en espèces lors de la livraison. Cette option est disponible pour les commandes inférieures à 100 000 FCFA. Un acompte peut être demandé pour les commandes importantes.'
                    ],
                    [
                        'question' => 'Mes données de paiement sont-elles sécurisées ?',
                        'answer' => 'Absolument. Nous utilisons des protocoles de sécurité avancés pour protéger vos informations. Aucune donnée bancaire n\'est stockée sur nos serveurs.'
                    ],
                    [
                        'question' => 'Puis-je obtenir une facture ?',
                        'answer' => 'Oui, une facture est automatiquement générée pour chaque commande. Elle vous sera envoyée par email et remise avec votre commande. Nous pouvons également établir des devis pour vos projets.'
                    ]
                ]
            ],
            [
                'category' => 'Support',
                'icon' => 'fas fa-headset',
                'color' => 'green',
                'questions' => [
                    [
                        'question' => 'Comment vous contacter ?',
                        'answer' => 'Vous pouvez nous contacter par téléphone au +228 98 70 60 81 ou +228 90 93 37 16, par email à agroshop_int@yahoo.fr, ou via notre chat en ligne. Nos conseillers sont disponibles du lundi au vendredi de 8h à 17h et le samedi de 9h à 16h.'
                    ],
                    [
                        'question' => 'Proposez-vous un service après-vente ?',
                        'answer' => 'Oui, nous assurons un service après-vente complet avec suivi technique, formation à l\'utilisation des produits, et support en cas de problème. Nos techniciens peuvent se déplacer si nécessaire.'
                    ],
                    [
                        'question' => 'Que faire en cas de produit défectueux ?',
                        'answer' => 'En cas de produit défectueux, contactez-nous immédiatement avec votre numéro de commande et des photos du problème. Nous procéderons à un échange ou remboursement selon la situation.'
                    ],
                    [
                        'question' => 'Organisez-vous des formations ?',
                        'answer' => 'Oui, nous organisons régulièrement des formations sur l\'utilisation des produits agricoles, les bonnes pratiques, et les nouvelles technologies. Consultez notre blog pour les prochaines sessions.'
                    ]
                ]
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $faqData,
        ]);
    }
}
