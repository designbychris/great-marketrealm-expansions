# The Keeper Turns the Key, the Sign Refuses to Change

Phase V.3A fixes frontend cache coherency for the Reading Room host page.

## The symptom

The Living Library activation state was correct, and the Browse desk immediately showed the new value, but the plain shortcode host page could still display an older cached Library overview.

That produced the confusing but technically consistent state:

```text
/expansions/
    → cached "Active"

/expansions/?gmrexp_section=browse
    → current "Inactive"
```

The problem was cached HTML, not duplicate activation state.

## Dynamic Reading Room host

Once the shortcode host page is known, GMREXP marks that WordPress page dynamic during `template_redirect`.

The Reading Room also marks the response dynamic while rendering the shortcode itself.

The standard WordPress/page-cache hint is:

```php
DONOTCACHEPAGE = true
```

and `nocache_headers()` is emitted when headers are still available.

This keeps WordPress-aware page caches from storing a Library overview whose activation labels can become stale.

## Cache invalidation after activation

After a successful V.3 Activate/Deactivate mutation, GMREXP resolves the remembered shortcode host page ID and calls:

```php
clean_post_cache($pageId)
```

This uses WordPress's standard post-cache invalidation boundary, allowing cache implementations that integrate with WordPress post invalidation to purge the host page.

If `clean_post_cache()` is unavailable but the object-cache API is present, GMREXP falls back to:

```php
wp_cache_delete($pageId, 'posts')
```

The activation state itself still lives only in the existing Living Library `ActivationStore`.

## Integration hooks

Two extension hooks are exposed for environments with additional caching layers:

```text
gmrexp/reading_room_dynamic
gmrexp/reading_room_activation_changed
```

The activation-changed hook receives:

```text
expansion key
new active state
Reading Room host page ID
```

This makes it possible to add a site-specific cache purge without coupling core GMREXP to a particular caching plugin or CDN.

## Important limitation

No WordPress plugin can guarantee removal of a page already intercepted by an unrelated reverse proxy or CDN that does not participate in WordPress invalidation.

V.3A therefore uses the strongest generic WordPress boundary available without adding a vendor-specific cache dependency. A completely external cache may require one manual purge when deploying this fix, after which `DONOTCACHEPAGE` prevents the Reading Room host from being cached again.

## Boundaries

V.3A does not change:

- Catalogue content;
- Expansion identities;
- Living Library activation semantics;
- compatibility rules;
- import/review/migration/publication state;
- Reading Room route contracts.

It only keeps the rendered sign on the front desk synchronized with the key the Keeper just turned.

Pippin insists the sign was technically correct "at the time it was painted."
