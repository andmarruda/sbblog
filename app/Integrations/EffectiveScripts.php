<?php

namespace App\Integrations;

use App\Enums\IntegrationPlacement;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationScope;
use App\Models\Article;
use App\Models\Blog;
use App\Models\Integration;
use Illuminate\Support\Collection;

/**
 * The scripts of one page: blog + author + article integrations, de-duplicated by
 * (provider, id), most specific scope first. Frontends run the same rules on the
 * integrations.updated events; this class powers the console preview.
 */
class EffectiveScripts
{
    /**
     * @return Collection<int, Integration>
     */
    public static function integrations(Blog $blog, ?Article $article = null): Collection
    {
        $all = Integration::query()
            ->whereBelongsTo($blog)
            ->where('enabled', true)
            ->where(fn ($query) => $query
                ->where('scope', IntegrationScope::Blog)
                ->when($article?->author_profile_id, fn ($q, string $authorId) => $q->orWhere(fn ($q) => $q->where('scope', IntegrationScope::Author)->where('author_profile_id', $authorId)))
                ->when($article?->id, fn ($q, string $articleId) => $q->orWhere(fn ($q) => $q->where('scope', IntegrationScope::Article)->where('article_id', $articleId))))
            ->get();

        $order = [IntegrationScope::Article->value => 0, IntegrationScope::Author->value => 1, IntegrationScope::Blog->value => 2];

        return $all
            ->filter(fn (Integration $integration) => self::matchesPage($integration, $article !== null))
            ->sortBy(fn (Integration $integration) => $order[$integration->scope->value])
            ->unique(fn (Integration $integration) => Providers::identity($integration->provider, $integration->config))
            ->values();
    }

    /**
     * HTML snippets by placement for one page.
     *
     * @return array{head: string, body_start: string, body_end: string}
     */
    public static function render(Blog $blog, ?Article $article = null): array
    {
        $integrations = self::integrations($blog, $article);
        $html = ['head' => [], 'body_start' => [], 'body_end' => []];

        // One gtag loader serves every GA4 and Google Ads ID.
        $gtag = $integrations->filter(fn (Integration $i) => in_array($i->provider, [IntegrationProvider::Ga4, IntegrationProvider::GoogleAds], true));

        if ($gtag->isNotEmpty()) {
            $html['head'][] = self::gtag($gtag, $article);
        }

        foreach ($integrations->diff($gtag) as $integration) {
            $html[$integration->placement->value][] = self::snippet($integration);
        }

        return array_map(fn (array $parts) => implode("\n", array_filter($parts)), $html);
    }

    private static function matchesPage(Integration $integration, bool $isArticlePage): bool
    {
        return match ($integration->page_rule['only'] ?? null) {
            'articles' => $isArticlePage,
            'home' => ! $isArticlePage,
            default => true,
        };
    }

    /**
     * @param  Collection<int, Integration>  $integrations
     */
    private static function gtag(Collection $integrations, ?Article $article): string
    {
        $ids = $integrations->map(fn (Integration $i) => (string) ($i->config['measurement_id'] ?? $i->config['conversion_id'] ?? ''))->filter()->values();
        $first = e($ids->first() ?? '');

        // Article pages send who/what was read as custom dimensions, so reports per author and
        // per article work in the analytics tool.
        $dimensions = $article === null ? '{}' : json_encode(array_filter([
            'article_id' => $article->id,
            'author_profile_id' => $article->author_profile_id,
            'category_id' => $article->category_id,
        ]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

        $configs = $ids->map(fn (string $id) => 'gtag("config",'.json_encode($id, JSON_HEX_TAG).','.$dimensions.');')->implode('');
        $consent = $integrations->contains(fn (Integration $i) => $i->provider === IntegrationProvider::GoogleAds) ? 'ads' : 'analytics';

        return "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$first}\" data-sbblog-consent=\"{$consent}\"></script>\n"
            ."<script data-sbblog-consent=\"{$consent}\">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\"js\",new Date());{$configs}</script>";
    }

    private static function snippet(Integration $integration): string
    {
        $config = $integration->config;
        $consent = $integration->consent_category->value;
        $attr = "data-sbblog-consent=\"{$consent}\"";

        return match ($integration->provider) {
            IntegrationProvider::Gtm => $integration->placement === IntegrationPlacement::BodyStart
                ? '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.e((string) $config['container_id']).'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>'
                : "<script {$attr}>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',".json_encode((string) $config['container_id'], JSON_HEX_TAG).');</script>',
            IntegrationProvider::MetaPixel => "<script {$attr}>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',".json_encode((string) $config['pixel_id'], JSON_HEX_TAG).");fbq('track','PageView');</script>",
            IntegrationProvider::Clarity => "<script {$attr}>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src='https://www.clarity.ms/tag/'+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,'clarity','script',".json_encode((string) $config['project_id'], JSON_HEX_TAG).');</script>',
            IntegrationProvider::Plausible => '<script defer data-domain="'.e((string) $config['domain']).'" src="'.e((string) ($config['script_url'] ?? 'https://plausible.io/js/script.js'))."\" {$attr}></script>",
            // Raw scripts are trusted HTML from people with scripts.raw (2FA, blog scope only).
            IntegrationProvider::Custom => '<!-- '.str_replace('--', '', (string) ($config['name'] ?? ''))." -->\n".(string) ($config['html'] ?? ''),
            default => '',
        };
    }
}
