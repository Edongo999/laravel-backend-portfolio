<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Article;
use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateArticles extends Command
{
    /**
     * Nom de la commande Artisan.
     */
    protected $signature = 'articles:translate';

    /**
     * Description.
     */
    protected $description = 'Retraduire tous les articles en anglais';

    /**
     * Exécution de la commande.
     */
    public function handle()
    {
        $this->info('🌍 Début de la traduction des articles...');

        try {
            $trEn = new GoogleTranslate('en');
        } catch (\Exception $e) {
            $this->error('❌ Impossible d’initialiser Google Translate.');
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $articles = Article::all();

        if ($articles->isEmpty()) {
            $this->warn('⚠️ Aucun article trouvé.');

            return Command::SUCCESS;
        }

        foreach ($articles as $article) {
            $this->info("🔄 Traduction de l'article #{$article->id}...");

            // Version française
            $article->title_fr = $article->title;
            $article->content_fr = $article->content;

            try {
                // Traduction du titre
                $article->title_en = $trEn->translate($article->title_fr);

                // Traduction du contenu
                $article->content_en = $trEn->translate($article->content_fr);

                // Sauvegarder
                $article->save();

                $this->info("✅ Article #{$article->id} traduit avec succès.");
            } catch (\Exception $e) {
                $message = $e->getMessage();

                $this->error(
                    "❌ Erreur pour l'article #{$article->id} : " . $message
                );
            }
        }

        $this->info('');
        $this->info(' Traduction terminée !');

        return Command::SUCCESS;
    }
}