<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Channel;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Services\Mercure\CookieGenerator;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\WebLink\Link;

class ChannelController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function getChannels(ChannelRepository $channelRepository): Response
    {
        $channels = $channelRepository->findAll();

        $response = $this->render('home/index.html.twig', [
            'channels' => $channels ?? []
        ]);
        $response->headers->set('Link', ['<http://localhost:3000/.well-known/mercure>', 'rel="mercure"']);

        return $response;
    }

    /**
     * @Route("/chat/{id}", name="chat")
     */
    public function chat(
        Request $request,
        Channel $channel,
        MessageRepository $messageRepository,
        CookieGenerator $cookieGenerator
    ): Response
    {
        $messages = $messageRepository->findBy([
            'channel' => $channel
        ], ['createdAt' => 'ASC']);

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure', $hubUrl));

        $response = $this->render('chat/index.html.twig', [
            'channel' => $channel,
            'messages' => $messages
        ]);
        $response->headers->setCookie(
            Cookie::create(
                'mercureAuthorization',
                $cookieGenerator($channel),
                new \DateTime('+1day'),
                '/.well-known/mercure'
            )
        );
        return $response;
    }
    /**
     * @Route("/c/back", name="app_chat", methods={"GET"})
     */
    public function index(ChannelRepository $channelRepository,MessageRepository $messageRepository,UserRepository $userRepository): Response
    {
        $channels = $this->getDoctrine()
            ->getRepository(Channel::class)
            ->findAll();
        $list1 = $channelRepository->calcul();
        $list2 = $messageRepository->calcul();
        $list3 = $userRepository->calcul();
        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable(
            [['Task', 'Hours per Day'],
                ['Channels',     $list1],
                ['Messages',    $list2],
                ['Users',    $list3],

            ]
        );
        $pieChart->getOptions()->setTitle('Reservation activities');
        $pieChart->getOptions()->setHeight(500);
        $pieChart->getOptions()->setWidth(900);
        $pieChart->getOptions()->getTitleTextStyle()->setBold(true);
        $pieChart->getOptions()->getTitleTextStyle()->setColor('#009900');
        $pieChart->getOptions()->getTitleTextStyle()->setItalic(true);
        $pieChart->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $pieChart->getOptions()->getTitleTextStyle()->setFontSize(20);

        return $this->render('chat/indexB.html.twig', [
            'messages' => $channels,
            'piechart' => $pieChart,
        ]);

    }
}
