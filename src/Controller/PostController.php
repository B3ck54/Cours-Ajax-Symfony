<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostLike;
use App\Repository\PostRepository;
use App\Repository\PostLikeRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(PostRepository $repo)
    {
        return $this->render('post/index.html.twig', [
            'posts' => $repo->findAll(),
        ]);
    }

    /**
     * Permet de liker ou unliker un article
     *
     * @Route("/post/{id}/like", name="post_like")
     * 
     * @param Post $post
     * @param ObjectManager $manager
     * @param PostLikeRepository $likeRepo
     * @return Response
     */
    public function like(Post $post, ObjectManager $manager, PostLikeRepository $likeRepo): Response
    {
        // La fonction reçoit un post en particulier, le manager pour enregistrer 
        // ou supprimer un like, un repo pour aller chercher le like

        // On récupère l'utilisateur courant
        $user = $this->getUser();

        //si l'utilisateur n'est pas connecté
        if (!$user) return $this->json([
            'code' => 403,
            'message' => 'Unauthorized',
        ], 403);

        // Si le post est liké par cet utilisateur on supprime le like
        if ($post->isLikedByUser($user)) {
            //on veut retrouver le like en question grâce au repo 
            //retrouver un like en fonction de ces différents critères (post et user)
            $like = $likeRepo->findOneBy([
                'post' => $post,
                'user' => $user
            ]);

            $manager->remove($like);
            $manager->flush();

            // on retourne la réponse au javascript d'en face
            return $this->json([
                'code' => 200,
                'message' => 'Like bien supprimé',
                'likes' => $likeRepo->count(['post' => $post]) //montre le nombre de like, que l'on compte sur ce post là
            ], 200);
        }

        // Si le post n'est pas encore liké par cet utilisatuer on ajoute le like
        $like = new PostLike;
        //on le configure en lui donnant l'article que l'on a aimé et l'user qui a aimé
        $like->setPost($post)
            ->setUser($user);

        $manager->persist($like);
        $manager->flush();

        return $this->json([
            'code' => 200,
            'message' => 'like bien ajouté',
            'likes' => $likeRepo->count(['post' => $post])
        ], 200);
    }
}
