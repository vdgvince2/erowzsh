<?php
/*
Fake Search page
*/

// don't display ads for this page
$noAds = true;

$pageTitle = "Search ".$WebsiteName;
$additionnalMetaDesc = "";

// en haut de la page, après config + $pdo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_keyword'], $_POST['email'])) {
    $result = create_search_alert($pdo, $_POST['alert_keyword'], $_POST['email']);
}

?>


<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<meta name="robots" value="noindex,nofollow">

<?php require __DIR__ . '/inc/head-scripts.php'; ?>

<body class="bg-gray-100">
<?php require __DIR__ . '/inc/header.php'; ?>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
  
    <div class="py-8 sm:py-12">
        <p class="text-xl"> <?=$label_subscription_thankyou;?> </p>
        <p class="" style="color:red">
            <strong><?php if(isset($result['success']) && $result['success'] == false) echo $result['message'];?></strong>
        </p>
    </div>

</main>



    <?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>