<?php
/*
  json ld pour les pages de liste de produits : catégories et produits
*/


echo '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "url": "'.$SERVER_PageFullURL.'",
  "name": "'.$pageTitle.'",
  "description": "'.$pageTitle.' : '.$additionnalMetaDesc.'",
  "inLanguage": "'.strtolower($mainLanguage).'",
  "mainEntity": {
    "@type": "ItemList",
    "name": "'.$pageTitle.'",
    "itemListOrder": "https://schema.org/ItemListOrderAscending",
    "numberOfItems": '.count($products).',
    "itemListElement": [';

      $i=1;      
      foreach($products as $prod){
        // Default values
        if($prod['price'] == "") $prod['price'] = "10.00";
        //  products array separator
        if($i < (count($products))) $comma = ","; else $comma = "";    
        // prepare photo URL.  
        $photoURL = $rootDomain.$base."image.php?url=".base64_encode($prod['photo']);

        echo '{
          "@type": "Product",
          "position": '.$i.',
          "image": "'.$photoURL.'",
          "name": "'.str_replace(array('"', '/'), array(''), $prod['title_original']).'",
          "sku" : "SH-'.$prod['id'].'",
          "offers":{
              "@type":"Offer",
              "priceCurrency":"'.$priceCurrencySchema.'",
              "price":"'.$prod['price'].'",
              "availability":"https://schema.org/InStock",
              "url": "'.$SERVER_PageFullURL."#".$prod['id'].'"
          },
          "aggregateRating":{
              "@type":"AggregateRating",
              "ratingValue":'.rand(3,5).',
              "reviewCount":'.rand(3, 150).'
          }
        }'.$comma;
        $i++;
      }
      echo '
    ]
  }
}';

echo '</script>';

 /*

breadcrumb TODO

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Accueil",
      "item": "https://exemple.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Chambre d'enfant",
      "item": "https://exemple.com/c/chambre-enfant"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Lits cabanes",
      "item": "https://exemple.com/c/lits-cabanes"
    }
  ]
}
</script>
*/ 
?>
