<?php
namespace App\Controller;
use App\Entity\Categorie;
use App\Entity\Enchere;
use App\Entity\ProfilVendeur;
use App\Entity\User;
use App\Repository\EnchereRepository;
use ContainerDKhXcz3\PaginatorInterface_82dac15;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Knp\Component\Pager\PaginatorInterface;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\EnchereType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Date;

ini_set('memory_limit', '-1');
class EnchereController extends AbstractFOSRestController
{
	
	
	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;
	
	/**
	 * @var EnchereRepository
	 */
	private $enchereRepository;
	
	
	public function __construct(EntityManagerInterface $entityManager, EnchereRepository $enchereRepository)
	{
		$this->entityManager = $entityManager;
		$this->enchereRepository = $enchereRepository;
	}
	
	/**
	 * @param Request $request
	 * @Rest\Get("/encheres", name="enchere_list")
	 * @return Response
	 */
	public function encheres_list()
	{
		$repository = $this->getDoctrine()->getRepository(Enchere::class);
		$encheres = $repository->findAll();
		return $this->handleView($this->view($encheres));
	}
	
	/**
	 * @param Request $request
	 * @Rest\Get("/enchere/{id}", name="enchere_show")
	 * @return Response
	 */
	public function getEnchereById(Enchere $id)
	{
		$data = $this->getDoctrine()->getRepository
		(Enchere::class)->find($id);
		return $this->handleView($this->view($data));
	}
	
	/** get appels selon le  vendeur
	 * @param Request $request
	 * @Rest\Get("/api/mesEncheres", name="enchere_vendeur")
	 * @return Response
	 */
	public function getEncherebyVendeur()
	{
		$profilVendeur = $this->getUser()->getProfilVendeur();
		$encheres = $profilVendeur->getEncheres();
		$data = $this->getDoctrine()->getRepository(
			Enchere::class)->findAll();
		return $this->handleView($this->view($encheres));
	}
	
	/** creation aenchere
	 * @param Request $request
	 * @Rest\Post("/api/enchere/{profilVendeur}")
	 * @return \FOS\RestBundle\View\View|Response
	 */
	
	public function new(Request $request, $profilVendeur)
	{
		$profilVendeur = $this->getDoctrine()->getRepository
		(ProfilVendeur::class)->find($profilVendeur);
		$em = $this->getDoctrine()->getManager();
		$description_ench = $request->request->get('description_ench');
		$date_debut = $request->request->get('date_debut');
		$date_fin = $request->request->get('date_fin');
		$prix_depart = $request->request->get('prix_depart');
		$nom_article = $request->request->get('nom_article');
		$description_article = $request->request->get('description_article');
		$categorie = $request->get('categorie');
		$image = $request->files->get('image');
		$cat = $this->getDoctrine()->getRepository
		(Categorie::class)->find($categorie);
		$enchere = new Enchere();
		$enchere->setDescriptionEnch($description_ench);
		$enchere->setDateDebut(new \DateTime($date_debut));
		$enchere->setDateFin(new \DateTime($date_fin));
		$enchere->setPrixDepart($prix_depart);
		$enchere->setPrixVente($prix_depart);
		$enchere->setNomArticle($nom_article);
		$enchere->setCategorie($cat);
		$enchere->setDescriptionArticle($description_article);
		$enchere->setProfilVendeur($profilVendeur);
		$fichier = md5(uniqid()) . '.' . $image->guessExtension();
		// On copie le fichier dans le dossier uploads
		$image->move(
			$this->getParameter('images_directory'),
			$fichier
		);
		// On crée l'image dans la base de données
		$enchere->setImage($fichier);
		$em->persist($enchere);
		$em->flush();
		return $this->handleView
		($this->view(['message' => 'enchere enregistré'], Response::HTTP_CREATED));
	}
	
	/** modification enchere
	 * @param Request $request
	 * @Rest\Patch("/api/enchere/{enchere}")
	 * @return \FOS\RestBundle\View\View|Response
	 */
	public function update(Request $request, $enchere): Response
	{
		
		$profilVendeur = $this->getUser()->getProfilVendeur();
		$enchere = $this->getDoctrine()->getRepository
		(Enchere::class)->find($enchere);
		$parameter = json_decode($request->getContent(), true);
		$date_debut = $request->get('dateDebut');
		$date_fin = $request->get('dateFin');
		$enchere->setDescriptionEnch($parameter['description_ench']);
		$enchere->setPrixDepart($parameter['prix_depart']);
		$enchere->setDateDebut(new \DateTime($date_debut));
		$enchere->setDateFin(new \DateTime($date_fin));
		$enchere->setNomArticle($parameter['nom_article']);
		$enchere->setDescriptionArticle($parameter['description_article']);
		$enchere->setImage($parameter['image']);
		$enchere->setCategorie($parameter['categorie']);
		$em = $this->getDoctrine()->getManager();
		$em->persist($enchere);
		$em->flush();
		return $this->handleView($this->view(['message' => 'enchere Modifie'], Response::HTTP_CREATED));
	}
	
	
	/** suppression enchere
	 * @param Request $request
	 * @Rest\Delete("/api/enchere/{enchere}")
	 * @return \FOS\RestBundle\View\View|Response
	 */
	public function deleteEnchere(Enchere $enchere): Response
	{
		$profilVendeur = $this->getUser()->getProfilVendeur();
		$enchere = $this->getDoctrine()->getRepository
		(Enchere::class)->find($enchere);
		$em = $this->getDoctrine()->getManager();
		$em->remove($enchere);
		$em->flush();
		return $this->json('Enchere supprimé');
	}
	
	/** liste participants
	 * @Rest\Get("/api/listeParticipants/{user}/{enchere}", name="liste_participants")
	 * @return Response
	 */
	public function listeParticipants($enchere)
	{
		$enchere = $this->getDoctrine()->getRepository
		(Enchere::class)->find($enchere);
		$list = $enchere->getParticipations();
		return $this->handleView($this->view($list));
	}
	
	
	/** liste des encheres terminee
	 * @Rest\Get("/api/encheresTerminees/{user}", name="liste_enchere_termine")
	 * @return Response
	 */
	public function termine(EnchereRepository $enchereRepository, $user)
	{
		$user = $this->getDoctrine()->getRepository
		(User::class)->find($user);
		
		$dateNow = new \DateTime();
		$list = $enchereRepository->createQueryBuilder('e')
			->andWhere('e.date_fin <= :date')
			->setParameter('date', $dateNow)
			->getQuery()
			->getResult();
		return $this->handleView($this->view($list));
	}
	
	/** liste des encheres terminee
	 * @Rest\Get("/api/encheresPlanifiees/{user}", name="liste_enchere_planifie")
	 * @return Response
	 */
	public function planifie(EnchereRepository $enchereRepository, $user)
	{
		$user = $this->getDoctrine()->getRepository
		(User::class)->find($user);
		$dateNow = new \DateTime();
		$list = $enchereRepository->createQueryBuilder('e')
			->andWhere('e.date_debut > :date')
			->setParameter('date', $dateNow)
			->getQuery()
			->getResult();
		return $this->handleView($this->view($list));
	}
	
	/** liste des encheres enCours
	 * @Rest\Get("/api/encheresEnCours/{user}", name="liste_enchere_enCours")
	 * @return Response
	 */
	public function enCours(EnchereRepository $enchereRepository, $user)
	{
		$user = $this->getDoctrine()->getRepository
		(User::class)->find($user);
		$dateNow = new \DateTime();
		$list = $enchereRepository->createQueryBuilder('e')
			->Where(' e.date_debut <= :date')
			->setParameter('date', $dateNow)
			->andWhere(' e.date_fin >= :date')
			->setParameter('date', $dateNow)
			->getQuery()
			->getResult();
		
		return $this->handleView($this->view($list));
	}
	
	
	/** liste des encheres terminee
	 * @Rest\Get("/encheresT")
	 * @return Response
	 */
	public function enchereT(EnchereRepository $enchereRepository)
	{
		$dateNow = new \DateTime();
		$list = $enchereRepository->createQueryBuilder('e')
			->andWhere('e.date_fin <= :date')
			->setParameter('date', $dateNow)
			->getQuery()
			->getResult();
		return $this->handleView($this->view($list));
	}
	
	
	

	
	
}
