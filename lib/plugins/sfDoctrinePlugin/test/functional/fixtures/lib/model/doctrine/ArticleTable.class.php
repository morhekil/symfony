<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class ArticleTable extends Doctrine_Table
{
  public function retrieveArticle1(Doctrine_Query $query)
  {
    return $query->execute();
  }

  public function retrieveArticle2(array $parameters)
  {
    $query = $this->createQuery('a');
    return $query->execute();
  }

  public function retrieveArticle3(array $parameters)
  {
    $query = $this->createQuery('a');
    return $query->execute();
  }

  public function retrieveArticle4(array $parameters)
  {
    $query = $this->createQuery('a');
    return $query->fetchOne();
  }

  public function routeTest9(array $parameters)
  {
    return Doctrine_Query::create()
      ->from('Article a')
      ->where('a.id = ?', $parameters['id'])
      ->limit(1)
      ->execute();
  }

  public function routeTest10(Doctrine_Query $q)
  {
    $q->orWhere($q->getRootAlias() . '.is_on_homepage = ?', 0);
    return $q->fetchOne();
  }
}