<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Message;
use App\Repository\ChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class MessageController extends AbstractController
{
    /**
     * @Route("/message", name="message", methods={"POST"})
     */
    public function sendMessage(
        Request $request,
        ChannelRepository $channelRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        PublisherInterface $publisher): JsonResponse
    {
        $data = \json_decode($request->getContent(), true);
        if (empty($content = $data['content'])) {
            throw new AccessDeniedHttpException('No data sent');
        }

        $channel = $channelRepository->findOneBy([
            'id' => $data['channel']
        ]);
        $content = $data['content'];

        $message = new Message();
        $message->setContent($content);
        $message->setChannel($channel);

        $em->persist($message);
        $em->flush();

        $jsonMessage = $serializer->serialize($message, 'json', [
            'groups' => ['message']
        ]);

        $update = new Update(sprintf('http://localhost:81/message/%s', $message->getId()), $jsonMessage);
        $publisher($update);

        return new JsonResponse(
            $jsonMessage,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/message/{id}", name="get_message", methods={"GET"})
     */
    public function getMessage(Message $message, SerializerInterface $serializer): JsonResponse
    {
        return new JsonResponse(
            $serializer->serialize($message, 'json', [
                'groups' => ['message']
            ]),
            Response::HTTP_OK,
            [],
            true
        );
    }
}