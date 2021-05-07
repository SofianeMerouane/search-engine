<?php

namespace App\Controller;

use App\Entity\Product;
use Elastica\Aggregation\Filter;
use Elastica\Aggregation\Terms;
use Elastica\Client;
use Elastica\Query;
use FOS\ElasticaBundle\Finder\TransformedFinder;
use http\QueryString;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Elastica\Util;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOS\ElasticaBundle\Manager\RepositoryManagerInterface;
use Elastica\Aggregation\Stats;


class SearchController extends AbstractController
{
    private $finder;
    private $client;



    public function __construct(PaginatedFinderInterface $finder, Client $client)
    {
        $this->finder = $finder;
        $this->client = $client;

    }
    /**
     * @Route("/",  name="search")
     */
    public function search(Request $request, SessionInterface $session, PaginatorInterface  $pagination): Response
    {
        $filterContry = $request->query->get('Contrys' );
        $filterCity = $request->query->get('Citys' );

        $q = (string) $request->query->get('q');

        $results = [];
        $countOfResults = 0;
        $filters = [];

        if ($q != "" || $filterContry){
            // on crée la query afin d'ajoter l'aggregation pour le filtre
            $query = new \Elastica\Query();
            $querString = new Query\QueryString();
            //création bool query
            $boolQuery = new \Elastica\Query\BoolQuery();

            // cr&tion query term pour lable
             /*$labelQuery = new \Elastica\Query\MatchQuery();*/
            $labelQuery = new \Elastica\Query\QueryString();

            $labelQuery->setFields(['label']);
            $labelQuery->setQuery('*'.$q.'*');
            $labelQuery->setFuzzyPrefixLength(2);
            $labelQuery->setAutoGeneratePhraseQueries(true);

            $boolQuery->addMust($labelQuery);

            // ajout des filtres
           if ($filterContry && $filterContry != ""){

               $contryQuery = new \Elastica\Query\Terms('contry',[$filterContry]);
               $boolQuery->addMust($contryQuery);
           }
            if ($filterCity && $filterCity != ""){
                $cityQuery = new \Elastica\Query\Terms('city',[$filterCity]);
                $boolQuery->addMust($cityQuery);
            }
            $query->setQuery($boolQuery);
            // création de l'agggregation contry
            $agg = new \Elastica\Aggregation\Terms('Contrys');
            $agg->setField('contry');
            $agg->setOrder('_term', 'asc');
            $agg->setSize(30000);
            $query->addAggregation($agg);

            // création de l'agggregation
            $aggCity = new \Elastica\Aggregation\Terms('Citys');
            $aggCity->setField('city');
           $aggCity->setOrder('_term', 'asc');
            $aggCity->setSize(30000);
            $query->addAggregation($aggCity);

            //recupére le resultat
            $result = $this->finder->createPaginatorAdapter($query);
            //envoi des filtre
            $filters = $this->finder->findPaginated($query)->getAdapter()->getAggregations();
            // envoi du nbr total de la recherche
            $countOfResults = $this->finder->findPaginated($query)->getNbResults();
            //pagination des resultats
            $results = $pagination->paginate($result, $request->query->getInt('page', 1),500);
        }

        return $this->render('search/index.html.twig', compact('results', 'q','filters','countOfResults','filterContry','filterCity' ));
    }

}
