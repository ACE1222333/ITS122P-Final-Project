<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About — BuyTheBella</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="shop-styles.css" />
  </head>
  <body>
    <!-- Nav, cart, auth, toast injected by shop-layout.js -->

    <!-- Hero split -->

    <div class="row g-0 align-items-stretch" style="min-height: 55vh">
      <div class="col-12 col-md-5 d-flex flex-column justify-content-center" style="background: var(--bg); padding: clamp(2.5rem, 5vw, 5rem) clamp(1.5rem, 4vw, 3rem)">
        <div class="text-uppercase mb-3" style="font-size: 0.68rem; letter-spacing: 0.14em; color: var(--text-muted)">Our Brand</div>
        <h1 style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: clamp(2.5rem, 4vw, 3.8rem); letter-spacing: 0.08em; line-height: 1.05">BuyTheBella</h1>
        <p style="color: var(--text-muted); font-size: 0.82rem; line-height: 1.6; max-width: 380px; margin-bottom: 0.75rem">
          <strong style="color: var(--text); font-size: 0.95rem; font-style: normal">Buy · The · Bella</strong>
        </p>
        <p class="fst-italic mb-0" style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.75; max-width: 380px">The name says it all — you're not just buying clothes, you're buying from <em>Bella</em>. Short for Arabella, the founder behind every piece. It's personal, it's direct, and it means every item you receive has been chosen by her hands and approved by her eye.</p>
      </div>
      <div class="col-md-7 d-none d-md-block" style="background: #d5d2cc">
        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=900&q=85&auto=format&fit=crop" alt="Curated women's fashion" loading="lazy" style="width: 100%; height: 100%; object-fit: cover" />
      </div>
    </div>

    <!-- About the owner -->
    <div style="background: var(--bg-card); border-top: 1px solid var(--border); padding: 4rem 1rem">
      <div class="container text-center" style="max-width: 700px">
        <h2 class="mb-4" style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.6rem; letter-spacing: 0.1em">About the Owner</h2>
        <h3 style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 2rem; letter-spacing: 0.08em">Arabella</h3>
        <div class="text-uppercase mb-3" style="font-size: 0.76rem; letter-spacing: 0.1em; color: var(--text-muted)">Founder &amp; Curator — "Bella"</div>
        <p style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.85">Hi, I'm <strong style="color: var(--text)">Arabella</strong> — or just Bella, as most people know me. I started selling secondhand clothes in 2021 out of a genuine love for fashion and a desire to make quality pieces more accessible. What began as a small Carousell side hustle slowly became something I couldn't stop thinking about.</p>
        <p class="fst-italic mb-0" style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.85">BuyTheBella is my name and my promise — every piece in this shop has passed through my hands, been inspected by my eyes, and has my personal stamp of approval. When you shop here, you're shopping directly from me.</p>
      </div>
    </div>

    <!-- OUR VALUES -->
    <div style="background: var(--bg-card); border-top: 1px solid var(--border); padding: 5rem 1rem">
      <div class="container" style="max-width: 960px">
        <div class="text-center mb-5">
          <div class="text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.14em; color: var(--text-muted)">What We Stand For</div>
          <h2 style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: clamp(2rem, 3.5vw, 3rem); letter-spacing: 0.1em">Why Secondhand?</h2>
        </div>

        <div class="row g-4">
          <div class="col-12 col-sm-6 col-lg-3">
            <div style="padding: 2rem 1.5rem; border: 1px solid var(--border); border-radius: 1rem; height: 100%">
              <div style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; margin-bottom: 0.6rem; text-align: center">Sustainability</div>
              <p style="font-size: 0.82rem; line-height: 1.75; color: var(--text-muted); margin: 0">Fast fashion is one of the world's biggest polluters. Every secondhand purchase means one less item sent to landfill — and one less reason to produce something new.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div style="padding: 2rem 1.5rem; border: 1px solid var(--border); border-radius: 1rem; height: 100%">
              <div style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; margin-bottom: 0.6rem; text-align: center">Quality</div>
              <p style="font-size: 0.82rem; line-height: 1.75; color: var(--text-muted); margin: 0">Nothing goes up unless it passes Bella's personal inspection. Condition, stitching, fabric — every detail is checked so you receive exactly what you see.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div style="padding: 2rem 1.5rem; border: 1px solid var(--border); border-radius: 1rem; height: 100%">
              <div style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; margin-bottom: 0.6rem; text-align: center">Affordability</div>
              <p style="font-size: 0.82rem; line-height: 1.75; color: var(--text-muted); margin: 0">Great style shouldn't be a luxury. Secondhand shopping makes it possible to wear pieces you love — without the retail markup that has nothing to do with the clothes themselves.</p>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-lg-3">
            <div style="padding: 2rem 1.5rem; border: 1px solid var(--border); border-radius: 1rem; height: 100%">
              <div style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; margin-bottom: 0.6rem; text-align: center">Honesty</div>
              <p style="font-size: 0.82rem; line-height: 1.75; color: var(--text-muted); margin: 0">Photos are real, descriptions are accurate, and flaws are always disclosed upfront. You deserve to know exactly what you're getting — no surprises, no disappointments.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- WHERE I STARTED -->
    <div style="background: var(--bg); border-top: 1px solid var(--border); padding: 5rem 1rem">
      <div class="container" style="max-width: 900px">
        <div class="text-center mb-5">
          <div class="text-uppercase mb-2" style="font-size: 0.68rem; letter-spacing: 0.14em; color: var(--text-muted)">The Origin Story</div>
          <h2 style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: clamp(2rem, 3.5vw, 3rem); letter-spacing: 0.1em">Where I Started</h2>
        </div>

        <div class="row align-items-center g-5">
          <!-- Carousell logo card -->
          <div class="col-12 col-md-4 text-center">
            <div style="display: inline-block; background: #fff; border-radius: 1.25rem; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08); padding: 2rem 2.5rem">
              <img src="https://images.seeklogo.com/logo-png/40/1/carousell-logo-png_seeklogo-408883.png" alt="Carousell logo" loading="lazy" style="width: 160px; max-width: 100%" />
              <div class="mt-3" style="font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted)">carousell.ph</div>
            </div>
          </div>

          <!-- Story text -->
          <div class="col-12 col-md-8">
            <p style="font-size: 0.93rem; line-height: 1.85; color: var(--text-muted)">It all began in <strong style="color: var(--text)">2021</strong> — long before BuyTheBella had a name or a brand identity. Armed with nothing but a smartphone camera and an eye for quality pieces, I created my very first seller profile on <em>Carousell</em>, one of Southeast Asia's most popular peer-to-peer marketplaces.</p>
            <p style="font-size: 0.93rem; line-height: 1.85; color: var(--text-muted)">What started as a way to declutter my own wardrobe quickly turned into something bigger. Listing after listing, I discovered that secondhand shopping wasn't just practical — it was a movement. Buyers weren't just looking for a bargain; they were searching for pieces with character, with history, and with a smaller footprint on the planet. That realization became the heartbeat of everything BuyTheBella stands for today.</p>
            <p style="font-size: 0.93rem; line-height: 1.85; color: var(--text-muted)">Those early Carousell days taught me how to communicate with buyers, how to price fairly, how to photograph clothing honestly, and above all — how to build trust one transaction at a time. Every lesson learned from that humble seller profile has been carried forward into this store.</p>

            <a href="https://www.carousell.ph/u/depop_bella?_branch_match_id=1591369097726522646&utm_source=share-native&utm_campaign=share-own-profile&utm_medium=sharing&_branch_referrer=H4sIAAAAAAAAAwXBS3KCMAAA0Nt0KdRPxc44DogKtAHCr4WNE9JAAoHQgAY2PXvfo9M0jO%2BahpEUj5FwvkLDsOKsbzWuqAPT8GlvylNVYs5%2Bjq7K13ODVCwYjThxooe5vvISujMwLYY73l6S6yXPfD%2FrKIsyHsGUD1AfHLzx9FwVFDtWhb4gBI27ABvsQIK3ge0qYNc7UJuW07wV%2Bu6zrT%2B2ySLZb3oWRexlkTEcXmc99PqbXiCnCPwEhEtVLkFtUnSTaP%2BdHe6IdPc9jkVwRg2MO9N4is6YmvyqXv4kqYiUrK%2FvpRRqJPJ4plJ05B8O2x4uAQEAAA%3D%3D" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.78rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text); text-decoration: none; border-bottom: 1px solid currentColor; padding-bottom: 2px; transition: opacity 0.2s" onmouseover="this.style.opacity = '.6'" onmouseout="this.style.opacity = '1'">
              <i class="bi bi-box-arrow-up-right"></i>
              Visit my original Carousell profile
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- CALL TO ACTION -->
    <div style="background: var(--text); color: var(--bg); padding: 5rem 1rem; text-align: center">
      <div class="container" style="max-width: 600px">
        <div class="text-uppercase mb-3" style="font-size: 0.68rem; letter-spacing: 0.16em; opacity: 0.6">Ready to Shop?</div>
        <h2 style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: clamp(2rem, 4vw, 3.2rem); letter-spacing: 0.08em; line-height: 1.1; margin-bottom: 1rem">Find Your Next<br />Favourite Piece</h2>
        <p style="font-size: 0.88rem; line-height: 1.8; opacity: 0.72; margin-bottom: 2rem">Every item is one-of-a-kind. Once it's gone, it's gone — so don't wait too long. Browse the current collection or reach out to Bella directly.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap">
          <button onclick="goPage('shop')" style="background: var(--bg); color: var(--text); border: none; padding: 0.8rem 2rem; font-family: &quot;DM Sans&quot;, sans-serif; font-size: 0.82rem; letter-spacing: 0.08em; text-transform: uppercase; border-radius: 4px; cursor: pointer; transition: opacity 0.2s" onmouseover="this.style.opacity = '.8'" onmouseout="this.style.opacity = '1'">Browse All Products</button>
          <button onclick="goPage('contacts')" style="background: transparent; color: var(--bg); border: 1px solid rgba(255, 255, 255, 0.4); padding: 0.8rem 2rem; font-family: &quot;DM Sans&quot;, sans-serif; font-size: 0.82rem; letter-spacing: 0.08em; text-transform: uppercase; border-radius: 4px; cursor: pointer; transition: opacity 0.2s" onmouseover="this.style.opacity = '.7'" onmouseout="this.style.opacity = '1'">Contact Bella</button>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="shop-shared.js?v=3"></script>
    <script src="shop-cart.js"></script>
    <script src="shop-auth.js"></script>
    <script src="shop-orders.js"></script>
    <script src="shop-reviews-modal.js"></script>
    <script src="shop-payment-form.js"></script>
    <script src="shop-layout.js"></script>
    <script>
      initShopLayout("about");
    </script>
  </body>
</html>
