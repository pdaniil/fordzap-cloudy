<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Аренда спецтехники в Москве и области — СпецТехПарк</title>
<meta name="description" content="Аренда экскаваторов, самосвалов, автокранов и дорожной техники. Собственный парк 60+ машин. Подача за 3 часа.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

<style>
/* ============ Vars & reset ============ */
:root{
  --accent:#5abcdc;
  --accent-dark:#3fa8cb;
  --accent-alt:#219fc8;
  --ink:#1B1F24;
  --ink-soft:#333;
  --muted:#6E7580;
  --line:#E4E7EA;
  --line-2:#C9CFD6;
  --bg-soft:#F4F6F8;
  --dark:#0E1B24;
  --dark-2:#132A38;
  --radius:4px;
  --container:1240px;
}
*,*::before,*::after{box-sizing:border-box;}
html{scroll-behavior:smooth;}
html,body{overflow-x:hidden;}
body{
  margin:0;font-family:'Open Sans',system-ui,sans-serif;font-size:15px;line-height:1.55;
  color:var(--ink);background:#fff;-webkit-font-smoothing:antialiased;
}
img{max-width:100%;display:block;}
a{color:inherit;text-decoration:none;}
button{font-family:inherit;cursor:pointer;border:0;background:none;color:inherit;}
input,select,textarea{font-family:inherit;font-size:15px;}
h1,h2,h3,h4{margin:0;font-weight:800;text-transform:uppercase;line-height:1.15;letter-spacing:.01em;}
h1{font-size:clamp(28px,4.2vw,46px);}
h2{font-size:clamp(22px,2.6vw,32px);}
h3{font-size:17px;}
p{margin:0;}
ul{list-style:none;padding:0;margin:0;}

.container{max-width:var(--container);margin:0 auto;padding:0 24px;}
.section{padding:80px 0;}
.section--soft{background:var(--bg-soft);}
.line{height:1px;background:var(--line);width:100%;}

.d-flex{display:flex;}
.justify-content{justify-content:space-between;}

/* ============ Buttons ============ */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  padding:14px 26px;border-radius:var(--radius);font-weight:700;font-size:13px;
  text-transform:uppercase;letter-spacing:.05em;
  transition:.18s;border:1px solid transparent;white-space:nowrap;
}
.btn-primary{background:var(--accent);color:#fff;}
.btn-primary:hover{background:var(--accent-dark);}
.btn-dark{background:var(--ink);color:#fff;}
.btn-dark:hover{background:#000;}
.btn-outline{background:#fff;color:var(--ink);border-color:var(--line-2);}
.btn-outline:hover{border-color:var(--accent);color:var(--accent);}
.btn-ghost-light{background:transparent;color:#fff;border-color:rgba(255,255,255,.4);}
.btn-ghost-light:hover{border-color:#fff;background:rgba(255,255,255,.08);}
.btn-block{width:100%;}
.btn--sm{padding:10px 16px;font-size:12px;}

/* ============ NAV-main-menu (permanent left sidebar) ============ */
#nav-main-menu{
  position:fixed;top:0;left:0;z-index:40;
  width:calc(463vw / 19.2);
  height:100vh;background:#fff;
  border-right:2px solid #ecf0f3;
  overflow-y:auto;
  transition:transform .3s ease;
  scrollbar-width:none;
  -ms-overflow-style:none;
}
#nav-main-menu::-webkit-scrollbar{width:0;}

#nav-main-menu .container{
  width:calc(338vw / 19.2);
  margin-left:auto;margin-right:auto;
  padding:0;
}

#nav-main-menu .logo{
  display:inline-flex;align-items:center;gap:10px;
  font-family:'Open Sans',sans-serif;font-weight:800;
  font-size:calc(22vw / 19.2);text-transform:uppercase;
  letter-spacing:.04em;color:var(--ink);
  padding-top:calc(40vh / 9.9);
  margin-bottom:calc(40vh / 9.9);
}
#nav-main-menu .logo-mark{
  width:calc(40vw / 19.2);height:calc(40vw / 19.2);
  background:var(--ink);color:var(--accent);
  display:flex;align-items:center;justify-content:center;
  border-radius:4px;font-size:calc(22vw / 19.2);
}
#nav-main-menu .logo span{color:var(--accent);}

#nav-main-menu .line{
  width:100%;height:1px;background:#ecf0f3;
  margin-bottom:calc(30vh / 9.9);
}

#nav-main-menu .text1{
  color:var(--accent-alt);
  font-family:'Open Sans',sans-serif;font-weight:700;
  text-transform:uppercase;
  font-size:calc(12vw / 19.2);
  letter-spacing:.13em;
}

#nav-main-menu ul{
  margin-bottom:calc(30vh / 9.9);
}

#nav-main-menu li{
  color:#1f222c;
  font-family:'Open Sans',sans-serif;
  font-size:calc(18vw / 19.2);
  margin-top:calc(30vh / 9.9);
}
#nav-main-menu li a{
  position:relative;
  display:inline-block;
  border-bottom:1px solid transparent;
  transition:color .3s, border-color .3s;
}
#nav-main-menu li a:hover{color:var(--accent-alt);border-color:var(--accent-alt);}

#nav-main-menu .dropdown{
  cursor:pointer;width:100%;
  display:flex;align-items:center;
}
#nav-main-menu .dropdown img,
#nav-main-menu .dropdown svg{
  align-self:center;margin-left:auto;
  transition:all .2s;
  width:calc(11vw / 19.2);
  height:auto;
}
#nav-main-menu .dropdown.active img,
#nav-main-menu .dropdown.active svg{
  transform:rotate(90deg);
}

#nav-main-menu .hidden_menu{
  display:none;margin-left:auto;
}
#nav-main-menu .hidden_menu.active{
  display:inline-block;
}
#nav-main-menu .hidden_menu li{
  color:#1f222c;
  font-size:calc(18vw / 19.2);
  margin-bottom:calc(30vh / 9.9);
}
#nav-main-menu .hidden_menu li:last-child{margin-bottom:0;}

#nav-main-menu .line:nth-child(6){
  margin-bottom:calc(55vh / 9.9);
}

#nav-main-menu .justify-content{
  color:#000;
  font-family:'Open Sans',sans-serif;
  text-transform:uppercase;
  font-size:calc(12vw / 19.2);
  letter-spacing:.13em;
  margin-bottom:calc(44vh / 9.9);
  gap:16px;
}
#nav-main-menu .justify-content a:hover{color:var(--accent-alt);}

#nav-main-menu .phone{
  display:flex;align-items:center;
}
#nav-main-menu .phone a{
  color:#1f222c;font-weight:700;
  font-size:calc(20vw / 19.2);
  margin-right:calc(20vh / 9.9);
}
#nav-main-menu .phone img,
#nav-main-menu .phone svg{
  max-width:calc(21vw / 19.2);max-height:calc(17vw / 19.2);
}

#nav-main-menu .email{
  display:flex;align-items:center;
  margin-top:calc(20vh / 9.9);
  margin-bottom:calc(40vh / 9.9);
}
#nav-main-menu .email a{
  color:#4e4e4e;
  font-size:calc(16vw / 19.2);
  margin-right:calc(20vh / 9.9);
}
#nav-main-menu .email img,
#nav-main-menu .email svg{
  max-width:calc(21vw / 19.2);max-height:calc(14vw / 19.2);
}

#nav-main-menu button,
#nav-main-menu .form_open{
  background-color:var(--accent);
  font-family:'Open Sans',sans-serif;font-weight:700;
  text-transform:uppercase;color:#fff;
  border:1px solid transparent;
  width:calc(269vw / 19.2);
  height:calc(50vw / 19.2);
  letter-spacing:calc(0.46vw / 19.2);
  font-size:calc(13vw / 19.2);
  transition:all .3s;
}
#nav-main-menu button:hover,
#nav-main-menu .form_open:hover{
  border:1px solid var(--accent);
  background-color:#fff;
  color:var(--accent);
}

/* Отступ для контента */
body{padding-left:calc(463vw / 19.2);}

/* Backdrop для мобильного меню */
.backdrop{
  position:fixed;inset:0;background:rgba(14,27,36,.5);z-index:35;
  opacity:0;pointer-events:none;transition:opacity .25s;
}
.backdrop.active{opacity:1;pointer-events:auto;}

@media (max-width:1024px){
  #nav-main-menu{
    transform:translateX(calc(-463vw / 10.24));
    width:calc(463vw / 10.24);
    border-right:0;
    z-index:100;
    box-shadow:6px 0 32px rgba(0,0,0,.12);
  }
  #nav-main-menu.active{transform:translateX(0);}
  body{padding-left:0;}

  #nav-main-menu .container{
    width:calc(338vw / 10.24);
    margin-left:calc(80vw / 10.24);
  }
  #nav-main-menu .logo{
    font-size:calc(22vw / 10.24);
    padding-top:calc(45vw / 10.24);
    margin-bottom:calc(45vw / 10.24);
  }
  #nav-main-menu .logo-mark{
    width:calc(36vw / 10.24);height:calc(36vw / 10.24);
    font-size:calc(20vw / 10.24);
  }
  #nav-main-menu .line{margin-bottom:calc(30vw / 10.24);}
  #nav-main-menu .text1{
    font-size:calc(11vw / 10.24);
    letter-spacing:calc(1.3vw / 10.24);
  }
  #nav-main-menu ul{margin-bottom:calc(30vw / 10.24);}
  #nav-main-menu li{
    font-size:calc(18vw / 10.24);
    margin-top:calc(30vw / 10.24);
  }
  #nav-main-menu .dropdown img,
  #nav-main-menu .dropdown svg{width:calc(11vw / 10.24);}
  #nav-main-menu .hidden_menu li{
    font-size:calc(18vw / 10.24);
    margin-bottom:calc(30vw / 10.24);
  }
  #nav-main-menu .line:nth-child(6){margin-bottom:calc(55vw / 10.24);}
  #nav-main-menu .justify-content{
    font-size:calc(11vw / 10.24);
    letter-spacing:calc(1.43vw / 10.24);
    margin-bottom:calc(44vw / 10.24);
  }
  #nav-main-menu .phone a{
    font-size:calc(20vw / 10.24);
    margin-right:calc(20vw / 10.24);
  }
  #nav-main-menu .phone img,
  #nav-main-menu .phone svg{
    max-width:calc(21vw / 10.24);max-height:calc(17vw / 10.24);
  }
  #nav-main-menu .email{
    margin-top:calc(20vw / 10.24);
    margin-bottom:calc(40vw / 10.24);
  }
  #nav-main-menu .email a{
    font-size:calc(16vw / 10.24);
    margin-right:calc(20vw / 10.24);
  }
  #nav-main-menu .email img,
  #nav-main-menu .email svg{
    max-width:calc(21vw / 10.24);max-height:calc(14vw / 10.24);
  }
  #nav-main-menu button,
  #nav-main-menu .form_open{
    width:calc(269vw / 10.24);
    height:calc(50vw / 10.24);
    letter-spacing:calc(0.46vw / 10.24);
    font-size:calc(13vw / 10.24);
    margin-bottom:calc(89vw / 10.24);
  }
}

@media (max-width:767px){
  #nav-main-menu{
    transform:translateX(calc(-390vw / 5.5));
    width:calc(390vw / 5.5);
  }
  #nav-main-menu .container{
    width:calc(315vw / 5.5);
    margin-left:calc(20vw / 3.2);
  }
  #nav-main-menu .logo{
    font-size:calc(22vw / 5.5);
    padding-top:calc(45vw / 5.5);
    margin-bottom:calc(45vw / 5.5);
  }
  #nav-main-menu .logo-mark{
    width:calc(36vw / 5.5);height:calc(36vw / 5.5);
    font-size:calc(20vw / 5.5);
  }
  #nav-main-menu .line{margin-bottom:calc(30vw / 5.5);}
  #nav-main-menu .text1{
    font-size:calc(11vw / 5.5);
    letter-spacing:calc(1.3vw / 5.5);
  }
  #nav-main-menu ul{margin-bottom:calc(30vw / 5.5);}
  #nav-main-menu li{
    font-size:calc(18vw / 5.5);
    margin-top:calc(30vw / 5.5);
  }
  #nav-main-menu .dropdown img,
  #nav-main-menu .dropdown svg{width:calc(11vw / 5.5);}
  #nav-main-menu .hidden_menu li{
    font-size:calc(18vw / 5.5);
    margin-bottom:calc(30vw / 5.5);
  }
  #nav-main-menu .line:nth-child(6){margin-bottom:calc(30vw / 5.5);}
  #nav-main-menu .justify-content{
    font-size:calc(11vw / 5.5);
    letter-spacing:calc(1.43vw / 5.5);
    margin-bottom:calc(44vw / 5.5);
  }
  #nav-main-menu .phone a{
    font-size:calc(20vw / 5.5);
    margin-right:calc(20vw / 5.5);
  }
  #nav-main-menu .phone img,
  #nav-main-menu .phone svg{
    max-width:calc(21vw / 5.5);max-height:calc(17vw / 5.5);
  }
  #nav-main-menu .email{
    margin-top:calc(20vw / 5.5);
    margin-bottom:calc(40vw / 5.5);
  }
  #nav-main-menu .email a{
    font-size:calc(16vw / 5.5);
    margin-right:calc(20vw / 5.5);
  }
  #nav-main-menu .email img,
  #nav-main-menu .email svg{
    max-width:calc(21vw / 5.5);max-height:calc(14vw / 5.5);
  }
  #nav-main-menu button,
  #nav-main-menu .form_open{
    width:calc(269vw / 5.5);
    height:calc(50vw / 5.5);
    letter-spacing:calc(0.46vw / 5.5);
    font-size:calc(13vw / 5.5);
    margin-bottom:calc(89vw / 5.5);
  }
}

/* ============ Header ============ */
.header{
  position:sticky;top:0;z-index:30;background:#fff;
  border-bottom:1px solid var(--line);
}
.header__inner{
  display:flex;align-items:center;justify-content:space-between;gap:32px;
  height:78px;
}
.hamburger{
  display:none;align-items:center;justify-content:center;
  width:44px;height:44px;flex-shrink:0;
}
.hamburger svg{width:26px;height:26px;stroke:var(--ink);}
.header__logo{
  display:flex;align-items:center;gap:10px;
  font-weight:800;font-size:17px;text-transform:uppercase;letter-spacing:.04em;
}
.header__logo-mark{
  width:34px;height:34px;background:var(--ink);color:var(--accent);
  display:flex;align-items:center;justify-content:center;
  font-size:18px;border-radius:var(--radius);
}
.header__logo span{color:var(--accent);}
.header__menu{display:flex;gap:28px;margin-left:auto;}
.header__menu a{
  font-size:13px;text-transform:lowercase;color:var(--ink-soft);
  padding-bottom:2px;border-bottom:1px solid transparent;transition:.15s;
}
.header__menu a:hover{color:var(--accent);border-color:var(--accent);}
.header__phone{
  font-weight:700;font-size:16px;white-space:nowrap;
}
.header__phone:hover{color:var(--accent);}
@media(max-width:1024px){
  .hamburger{display:flex;}
}
@media(max-width:1024px){
  .header__menu{display:none;}
  .header__logo{font-size:15px;}
}
@media(max-width:520px){
  .header__phone{display:none;}
}

/* ============ Slider base ============ */
.swiper{position:relative;overflow:hidden;cursor:grab;}
.swiper:active{cursor:grabbing;}
.swiper-wrapper{display:flex;height:100%;transition:transform .42s ease;will-change:transform;}
.swiper-slide{flex:0 0 auto;position:relative;}
.arrow.swiper-button-disabled{opacity:.3;pointer-events:none;}

/* ============ Hero ============ */
.hero{padding-top:32px;}
.crumbs{
  display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted);
  margin-bottom:28px;
}
.crumbs a:hover{color:var(--accent);}
.crumbs .sep{color:#bbb;font-size:14px;}
.hero__head{
  display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:start;
  margin-bottom:44px;
}
@media(max-width:900px){.hero__head{grid-template-columns:1fr;gap:20px;}}
.hero__title span{color:var(--accent);display:block;}
.hero__lead{color:var(--muted);font-size:15px;line-height:1.7;margin-top:6px;}
@media(max-width:900px){.hero__lead{margin-top:0;}}

.hero__slider{position:relative;margin-bottom:56px;}
.hero__slider .swiper{width:100%;height:min(70vh,560px);}
.hero__slide{
  background-size:cover;background-position:center;display:flex;align-items:flex-end;
  background-color:#0E1B24;
}
.hero__info{
  padding:48px;color:#fff;width:100%;
  background:linear-gradient(transparent,rgba(14,27,36,.75));
}
.hero__info-cat{font-size:12px;letter-spacing:.12em;text-transform:uppercase;opacity:.85;}
.hero__info-name{
  font-family:'Open Sans',sans-serif;font-weight:800;font-size:clamp(22px,3vw,36px);
  text-transform:uppercase;margin-top:8px;letter-spacing:.02em;
}

.arrow{
  position:absolute;top:50%;transform:translateY(-50%);z-index:5;
  width:44px;height:44px;border-radius:50%;background:#fff;color:var(--ink);
  display:flex;align-items:center;justify-content:center;font-size:20px;
  box-shadow:0 4px 16px rgba(0,0,0,.14);transition:.15s;
}
.arrow:hover{background:var(--accent);color:#fff;}
.arrow#hPrev,.arrow#ePrev,.arrow#pPrev{left:20px;}
.arrow#hNext,.arrow#eNext,.arrow#pNext{right:20px;}

/* ============ Filter / catalog ============ */
.catalog{padding:64px 0 80px;background:#fff;}
.filter{
  background:var(--bg-soft);border-radius:var(--radius);
  padding:32px 32px 28px;margin-bottom:48px;
}
.filter__row{
  display:grid;grid-template-columns:1fr 1fr;gap:56px;margin-bottom:32px;
}
@media(max-width:820px){.filter__row{grid-template-columns:1fr;gap:28px;}}
.filter__label{
  font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);
  font-weight:700;margin-bottom:14px;
}
.range-row{display:flex;align-items:center;gap:14px;margin-bottom:8px;}
.range-row .line-bar{flex:1;height:2px;background:var(--line-2);}
.range-row b{font-weight:700;font-size:15px;white-space:nowrap;}

.dr{position:relative;padding:14px 0 6px;}
.dr__track{position:relative;height:3px;background:var(--line-2);border-radius:2px;}
.dr__fill{position:absolute;top:0;bottom:0;background:var(--accent);border-radius:2px;}
.dr__handle{
  position:absolute;top:50%;width:16px;height:16px;margin:-8px 0 0 -8px;
  border-radius:50%;background:#fff;
  box-shadow:0 0 0 2px var(--accent),0 2px 4px rgba(0,0,0,.12);
  cursor:grab;touch-action:none;
}
.dr__handle:active{cursor:grabbing;}

.filter__checks{
  display:grid;grid-template-columns:repeat(3,1fr) auto;gap:32px;align-items:end;
  padding-top:24px;border-top:1px solid var(--line);
}
@media(max-width:1000px){.filter__checks{grid-template-columns:repeat(2,1fr);}}
@media(max-width:640px){.filter__checks{grid-template-columns:1fr;gap:20px;}}

.checks{display:flex;flex-wrap:wrap;gap:8px;}
.chk{display:inline-flex;cursor:pointer;}
.chk input{position:absolute;opacity:0;pointer-events:none;}
.chk span{
  display:inline-block;padding:8px 14px;background:#fff;
  border:1px solid var(--line-2);border-radius:var(--radius);
  font-size:13px;transition:.15s;user-select:none;
}
.chk input:checked + span{background:var(--accent);border-color:var(--accent);color:#fff;}
.chk:hover span{border-color:var(--accent);}

.filter__meta{
  display:flex;justify-content:space-between;align-items:center;
  padding-top:24px;border-top:1px solid var(--line);margin-top:24px;
}
.found{font-size:14px;color:var(--muted);}
.found b{color:var(--ink);font-weight:700;}
.sort{position:relative;}
.sort__toggle{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 16px;border:1px solid var(--line-2);border-radius:var(--radius);
  background:#fff;font-size:13px;font-weight:600;
}
.sort__toggle .chev{transition:transform .2s;}
.sort.open .sort__toggle .chev{transform:rotate(180deg);}
.sort__menu{
  position:absolute;top:calc(100% + 6px);right:0;min-width:200px;z-index:15;
  background:#fff;border:1px solid var(--line);border-radius:var(--radius);
  box-shadow:0 12px 32px rgba(0,0,0,.08);padding:6px 0;
  opacity:0;visibility:hidden;transform:translateY(-6px);transition:.15s;
}
.sort.open .sort__menu{opacity:1;visibility:visible;transform:translateY(0);}
.sort__menu button{
  display:block;width:100%;text-align:left;padding:10px 16px;
  font-size:13px;color:var(--ink-soft);
}
.sort__menu button:hover{background:var(--bg-soft);color:var(--accent);}

/* ============ Equipment grid ============ */
.grid{
  display:grid;grid-template-columns:repeat(2,1fr);gap:32px 32px;
}
@media(max-width:760px){.grid{grid-template-columns:1fr;gap:24px;}}

.card{
  display:flex;flex-direction:column;background:#fff;
  border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;
  transition:.2s;
}
.card:hover{border-color:var(--line-2);box-shadow:0 12px 32px rgba(14,27,36,.06);}
.card__img{
  aspect-ratio:16/9;overflow:hidden;position:relative;background:#EAEDF1;
}
.card__img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
.card:hover .card__img img{transform:scale(1.04);}
.card__tag{
  position:absolute;top:14px;left:14px;
  padding:5px 12px;background:rgba(14,27,36,.85);color:#fff;
  font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  border-radius:100px;
}
.card__body{padding:24px 24px 22px;display:flex;flex-direction:column;gap:16px;flex:1;}
.card__title{font-size:16px;font-weight:700;text-transform:none;letter-spacing:0;}
.card__specs{
  display:grid;grid-template-columns:repeat(3,1fr);gap:12px;
  padding:16px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);
}
.card__specs b{display:block;font-weight:700;font-size:14px;}
.card__specs span{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.card__foot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;}
.card__price{font-weight:800;font-size:18px;}
.card__price small{display:block;font-size:11px;color:var(--muted);font-weight:400;margin-top:2px;}

.load-more-wrap{margin-top:48px;text-align:center;}
.load-more-wrap .line{margin-bottom:28px;}
.load-more{
  display:inline-flex;align-items:center;gap:10px;
  padding:14px 28px;border:1px solid var(--ink);border-radius:var(--radius);
  font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.05em;
  transition:.15s;
}
.load-more:hover{background:var(--ink);color:#fff;}
.load-more::after{content:'›';font-size:20px;line-height:1;}

/* ============ Promo banner slider ============ */
.promo{position:relative;}
.promo .swiper{width:100%;height:min(60vh,460px);}
.promo__slide{background-size:cover;background-position:center;display:flex;align-items:center;justify-content:center;background-color:#0E1B24;}
.promo__info{
  max-width:620px;padding:44px 48px;text-align:center;color:#fff;
  background:rgba(14,27,36,.55);border-radius:var(--radius);
}
.promo__title{font-size:clamp(20px,2.6vw,30px);font-weight:800;text-transform:uppercase;margin-bottom:20px;}
.promo__btn{
  display:inline-flex;align-items:center;gap:8px;padding:12px 24px;
  background:var(--accent);color:#fff;border-radius:var(--radius);
  font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.05em;
}
.promo__btn:hover{background:var(--accent-dark);}

/* ============ Portfolio slider ============ */
.portfolio{padding:80px 0;position:relative;}
.portfolio h2{margin-bottom:36px;}
.portfolio .swiper{padding:0 60px 40px;}
.pf-card{display:block;}
.pf-card__img{aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius);background:#EAEDF1;}
.pf-card__img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
.pf-card:hover .pf-card__img img{transform:scale(1.05);}
.pf-card__name{font-weight:700;font-size:15px;margin-top:14px;}
.pf-card__meta{color:var(--muted);font-size:13px;margin-top:4px;}
.portfolio__foot{margin-top:24px;text-align:center;}

/* ============ About ============ */
.about{display:grid;grid-template-columns:1.05fr 1fr;gap:64px;align-items:center;}
@media(max-width:900px){.about{grid-template-columns:1fr;gap:32px;}}
.about__media{
  position:relative;border-radius:var(--radius);overflow:hidden;aspect-ratio:4/3;background:#EAEDF1;
}
.about__media img{width:100%;height:100%;object-fit:cover;}
.about__badge{
  position:absolute;bottom:24px;left:24px;color:#fff;z-index:2;
}
.about__badge::before{
  content:'';position:absolute;inset:-14px -18px;background:rgba(14,27,36,.7);
  border-radius:var(--radius);z-index:-1;
}
.about__badge b{font-size:30px;font-weight:800;display:block;line-height:1;}
.about__badge span{font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.85;}
.about__kicker{
  display:inline-block;font-size:12px;font-weight:700;letter-spacing:.1em;
  text-transform:uppercase;color:var(--accent);margin-bottom:12px;
}
.about p{color:var(--muted);margin-top:18px;line-height:1.7;}
.about__nums{
  display:grid;grid-template-columns:repeat(2,1fr);gap:24px;margin-top:32px;
}
.about__nums > div{border-top:2px solid var(--accent);padding-top:14px;}
.about__nums b{font-size:26px;font-weight:800;display:block;line-height:1;}
.about__nums span{font-size:12px;color:var(--muted);margin-top:6px;display:block;}

/* ============ Advantages ============ */
.adv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
@media(max-width:900px){.adv-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.adv-grid{grid-template-columns:1fr;}}
.adv{
  padding:32px 28px;background:#fff;border:1px solid var(--line);
  border-radius:var(--radius);display:flex;flex-direction:column;gap:14px;
  transition:.2s;
}
.adv:hover{border-color:var(--accent);}
.adv__icon{
  width:52px;height:52px;border-radius:50%;background:#E8F4F9;color:var(--accent);
  display:flex;align-items:center;justify-content:center;
}
.adv__icon svg{width:24px;height:24px;}
.adv h3{font-size:15px;}
.adv p{color:var(--muted);font-size:14px;line-height:1.65;}

/* ============ Reviews ============ */
.reviews{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
@media(max-width:960px){.reviews{grid-template-columns:repeat(2,1fr);}}
@media(max-width:640px){.reviews{grid-template-columns:1fr;}}
.review{
  background:#fff;padding:28px;border:1px solid var(--line);border-radius:var(--radius);
  display:flex;flex-direction:column;gap:18px;
}
.review__head{display:flex;gap:14px;align-items:center;}
.review__avatar{
  width:48px;height:48px;border-radius:50%;background:var(--accent);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:16px;flex-shrink:0;
}
.review__name{font-weight:700;font-size:14px;text-transform:none;letter-spacing:0;}
.review__role{font-size:12px;color:var(--muted);}
.review__text{font-size:14px;color:var(--ink-soft);line-height:1.65;}
.review__stars{color:var(--accent);font-size:13px;letter-spacing:2px;}

.logos{
  display:grid;grid-template-columns:repeat(6,1fr);gap:16px;margin-top:48px;
}
@media(max-width:900px){.logos{grid-template-columns:repeat(3,1fr);}}
@media(max-width:520px){.logos{grid-template-columns:repeat(2,1fr);}}
.logo-item{
  display:flex;align-items:center;justify-content:center;height:72px;
  border:1px solid var(--line);border-radius:var(--radius);background:#fff;
  color:#9AA5B1;font-weight:800;font-size:14px;letter-spacing:.04em;
  transition:.2s;
}
.logo-item:hover{color:var(--accent);border-color:var(--accent);}

/* ============ SEO text ============ */
.seo{padding:80px 0;}
.seo h2{margin-bottom:28px;}
.seo__body{max-width:900px;color:var(--ink-soft);line-height:1.75;}
.seo__body p{margin-bottom:16px;}
.seo__body ul{padding-left:22px;list-style:disc;margin-bottom:16px;}
.seo__body li{margin-bottom:6px;}
.seo__body b{color:var(--ink);}

/* ============ CTA ============ */
.cta{padding:72px 0;background:#fff;}
.cta h2{margin-bottom:36px;}
.cta__grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
@media(max-width:820px){.cta__grid{grid-template-columns:1fr;}}
.cta__card{
  padding:44px 40px;border-radius:var(--radius);color:#fff;
  background:linear-gradient(135deg,var(--dark) 0%,var(--dark-2) 100%);
  position:relative;overflow:hidden;
}
.cta__card::before{
  content:'';position:absolute;right:-80px;top:-80px;width:280px;height:280px;
  border-radius:50%;background:radial-gradient(circle, rgba(90,188,220,.25) 0%, transparent 70%);
}
.cta__title{font-size:clamp(20px,2.4vw,28px);font-weight:800;text-transform:uppercase;line-height:1.2;position:relative;}
.cta__text{margin:16px 0 28px;color:#C9D4DD;font-size:14px;line-height:1.7;position:relative;}
.cta__buttons{display:flex;gap:12px;flex-wrap:wrap;position:relative;}

/* ============ Contacts ============ */
.contacts{padding:80px 0;}
.contacts h2{margin-bottom:36px;}
.contacts__grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:stretch;}
@media(max-width:900px){.contacts__grid{grid-template-columns:1fr;}}
.contacts__list{display:flex;flex-direction:column;gap:26px;}
.contacts__item{display:flex;gap:18px;align-items:flex-start;}
.contacts__icon{
  width:48px;height:48px;border-radius:50%;background:#E8F4F9;color:var(--accent);
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.contacts__icon svg{width:20px;height:20px;}
.contacts__label{font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);margin-bottom:4px;}
.contacts__value{font-weight:600;font-size:15px;}
.contacts__value a:hover{color:var(--accent);}
.map{border-radius:var(--radius);overflow:hidden;min-height:400px;background:var(--bg-soft);}
.map iframe{width:100%;height:100%;border:0;display:block;min-height:400px;}

/* ============ Form ============ */
.form-section{
  padding:80px 0;color:#fff;position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--dark) 0%,var(--dark-2) 100%);
}
.form-section::before{
  content:'';position:absolute;left:-120px;bottom:-160px;width:520px;height:520px;
  border-radius:50%;background:radial-gradient(circle, rgba(90,188,220,.18) 0%, transparent 70%);
}
.form-wrap{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center;position:relative;z-index:2;}
@media(max-width:900px){.form-wrap{grid-template-columns:1fr;gap:40px;}}
.form-wrap h2{color:#fff;}
.form-wrap .form-lead{color:#C9D4DD;margin-top:18px;line-height:1.7;}
.form-wrap ul{margin-top:28px;display:flex;flex-direction:column;gap:12px;}
.form-wrap li{display:flex;gap:12px;align-items:flex-start;color:#C9D4DD;font-size:14px;}
.form-wrap li::before{
  content:'';flex-shrink:0;width:20px;height:20px;border-radius:50%;
  background:var(--accent);margin-top:2px;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'><polyline points='20 6 9 17 4 12'/></svg>");
  background-size:11px;background-position:center;background-repeat:no-repeat;
}
.form-card{
  background:#fff;color:var(--ink);border-radius:var(--radius);padding:40px;
  box-shadow:0 24px 64px rgba(0,0,0,.3);
}
.form-card h3{font-size:18px;margin-bottom:6px;text-transform:uppercase;}
.form-card .form-sub{color:var(--muted);font-size:13px;margin-bottom:22px;}
.form-card label{display:block;margin-bottom:14px;}
.form-card label span{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;letter-spacing:.04em;text-transform:uppercase;}
.form-card input,.form-card select,.form-card textarea{
  width:100%;padding:13px 14px;border:1px solid var(--line-2);border-radius:var(--radius);
  outline:none;transition:border-color .15s;background:#fff;
}
.form-card input:focus,.form-card select:focus,.form-card textarea:focus{border-color:var(--accent);}
.form-card textarea{resize:vertical;min-height:80px;}
.form-card .agree{font-size:11.5px;color:var(--muted);margin-top:14px;line-height:1.5;}
.form-card .agree a{color:var(--accent);text-decoration:underline;}
.form-success{display:none;text-align:center;padding:40px 20px;}
.form-success.show{display:block;}
.form-success__icon{
  width:64px;height:64px;border-radius:50%;background:var(--accent);color:#fff;
  display:flex;align-items:center;justify-content:center;margin:0 auto 20px;
}
.form-success__icon svg{width:28px;height:28px;}
.form-success h3{text-transform:uppercase;font-size:18px;}

/* ============ Footer ============ */
.footer{background:var(--dark);color:#8A98A5;padding:64px 0 32px;font-size:14px;}
.footer__top{
  display:grid;grid-template-columns:repeat(3,1fr);gap:48px;
  padding-bottom:44px;border-bottom:1px solid rgba(255,255,255,.08);
}
@media(max-width:820px){.footer__top{grid-template-columns:1fr 1fr;gap:32px;}}
@media(max-width:520px){.footer__top{grid-template-columns:1fr;}}
.footer h4{
  color:#fff;font-size:12px;font-weight:700;letter-spacing:.1em;
  text-transform:uppercase;margin-bottom:18px;
}
.footer ul{display:flex;flex-direction:column;gap:10px;}
.footer a{color:#8A98A5;transition:.15s;}
.footer a:hover{color:var(--accent);}
.footer__contacts{
  display:flex;flex-wrap:wrap;gap:36px;padding:32px 0;border-bottom:1px solid rgba(255,255,255,.08);
}
.footer__contact{display:flex;align-items:center;gap:12px;color:#C9D4DD;}
.footer__contact svg{width:18px;height:18px;color:var(--accent);flex-shrink:0;}
.footer__contact a:hover{color:var(--accent);}
.footer__bottom{
  display:flex;justify-content:space-between;align-items:center;gap:24px;
  padding-top:28px;flex-wrap:wrap;font-size:13px;
}
.footer__bottom .search{
  display:flex;align-items:center;background:rgba(255,255,255,.06);
  border-radius:var(--radius);overflow:hidden;
}
.footer__bottom .search input{
  background:transparent;border:0;outline:none;color:#fff;padding:11px 16px;min-width:200px;
}
.footer__bottom .search input::placeholder{color:#8A98A5;}
.footer__bottom .search button{
  padding:11px 18px;background:var(--accent);color:#fff;font-weight:700;
}
.footer__bottom .search button::after{content:'→';}
</style>
</head>
<body>

<!-- ============ NAV-main-menu ============ -->
<nav id="nav-main-menu" aria-hidden="false">
  <div class="container">
    <a href="#" class="logo">
      <span class="logo-mark">С</span>
      СпецТех<span>Парк</span>
    </a>
  </div>
  <div class="line"></div>

  <div class="container">
    <div class="text1">техника</div>
    <ul>
      <li><a href="#equipment" data-close>Земляные работы</a></li>
      <li><a href="#equipment" data-close>Подъём и краны</a></li>
      <li><a href="#equipment" data-close>Дорожные работы</a></li>
      <li><a href="#equipment" data-close>Грузоперевозки</a></li>
    </ul>
  </div>
  <div class="line"></div>

  <div class="container">
    <div class="text1">услуги</div>
    <ul>
      <li><a href="#" data-close>Аренда с оператором</a></li>
      <li><a href="#" data-close>Аренда без оператора</a></li>
      <li class="dropdown" id="navDropdown">
        <div>Доставка техники</div>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 6 15 12 9 18"/>
        </svg>
      </li>
      <div class="d-flex">
        <div class="hidden_menu" id="navHiddenMenu">
          <li><a href="#" data-close>Трал</a></li>
          <li><a href="#" data-close>Низкорамная платформа</a></li>
          <li><a href="#" data-close>Своим ходом</a></li>
        </div>
      </div>
      <li><a href="#" data-close>Разработка грунта</a></li>
      <li><a href="#" data-close>Демонтажные работы</a></li>
    </ul>
  </div>
  <div class="line"></div>

  <div class="container">
    <div class="d-flex justify-content">
      <a href="#about" data-close>о компании</a>
      <a href="#portfolio" data-close>портфолио</a>
      <a href="#contacts" data-close>контакты</a>
    </div>

    <div class="d-flex phone">
      <a href="tel:+74951234567">+7 (495) 123-45-67</a>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1f222c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>
      </svg>
    </div>

    <div class="d-flex email">
      <a href="mailto:info@spectechpark.ru">info@spectechpark.ru</a>
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4e4e4e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
    </div>

    <button class="form_open" data-open-form>Заказать звонок</button>
  </div>
</nav>
<div class="backdrop" id="backdrop"></div>

<!-- ============ Header ============ -->
<header class="header">
  <div class="container header__inner">
    <button class="hamburger" id="burger" aria-label="Меню" aria-expanded="false">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
        <line x1="3" y1="7" x2="21" y2="7"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="17" x2="21" y2="17"/>
      </svg>
    </button>

    <a href="#" class="header__logo">
      <span class="header__logo-mark">С</span>
      СпецТех<span>Парк</span>
    </a>

    <nav class="header__menu">
      <a href="#about">о компании</a>
      <a href="#equipment">техника</a>
      <a href="#contacts">контакты</a>
    </nav>

    <a href="tel:+74951234567" class="header__phone">+7 (495) 123-45-67</a>
  </div>
</header>

<!-- ============ Hero ============ -->
<section class="hero">
  <div class="container">
    <div class="crumbs">
      <a href="/">Главная</a>
      <span class="sep">›</span>
      <span>Аренда спецтехники</span>
    </div>

    <div class="hero__head">
      <h1 class="hero__title">Аренда спецтехники <span>в Москве и области</span></h1>
      <p class="hero__lead">
        Собственный парк из 60+ единиц техники: экскаваторы, самосвалы, автокраны,
        дорожная техника. Работаем с операторами и без, подача на объект за 3 часа,
        полный пакет документов, работа с НДС.
      </p>
    </div>
  </div>

  <div class="hero__slider">
    <div class="swiper" id="heroSwiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide hero__slide" data-ph="Экскаватор JCB 3CX" data-ph-w="1920" data-ph-h="1000">
          <div class="hero__info">
            <div class="hero__info-cat">Земляные работы</div>
            <div class="hero__info-name">Экскаватор-погрузчик JCB 3CX</div>
          </div>
        </div>
        <div class="swiper-slide hero__slide" data-ph="Автокран Liebherr" data-ph-w="1920" data-ph-h="1000">
          <div class="hero__info">
            <div class="hero__info-cat">Подъём и краны</div>
            <div class="hero__info-name">Автокран Liebherr LTM 1050</div>
          </div>
        </div>
        <div class="swiper-slide hero__slide" data-ph="Самосвал Volvo FMX" data-ph-w="1920" data-ph-h="1000">
          <div class="hero__info">
            <div class="hero__info-cat">Грузоперевозки</div>
            <div class="hero__info-name">Самосвал Volvo FMX 20 м³</div>
          </div>
        </div>
      </div>
    </div>
    <button class="arrow" id="hPrev" aria-label="Назад">‹</button>
    <button class="arrow" id="hNext" aria-label="Вперёд">›</button>
  </div>
</section>

<!-- ============ Catalog with filter ============ -->
<section class="catalog" id="equipment">
  <div class="container">
    <form class="filter" onsubmit="return false">
      <div class="filter__row">
        <div>
          <div class="filter__label">Грузоподъёмность, т</div>
          <div class="range-row">
            <b>от <span data-min="weight">1</span></b>
            <div class="line-bar"></div>
            <b>до <span data-max="weight">50</span></b>
          </div>
          <div id="sliderWeight"></div>
        </div>
        <div>
          <div class="filter__label">Стоимость смены, тыс ₽</div>
          <div class="range-row">
            <b>от <span data-min="price">10</span></b>
            <div class="line-bar"></div>
            <b>до <span data-max="price">25</span></b>
          </div>
          <div id="sliderPrice"></div>
        </div>
      </div>

      <div class="filter__checks">
        <div>
          <div class="filter__label">Категория</div>
          <div class="checks">
            <label class="chk"><input type="checkbox" value="earth"><span>Земляные</span></label>
            <label class="chk"><input type="checkbox" value="lift"><span>Подъём</span></label>
            <label class="chk"><input type="checkbox" value="road"><span>Дорожные</span></label>
            <label class="chk"><input type="checkbox" value="truck"><span>Перевозки</span></label>
          </div>
        </div>

        <div>
          <div class="filter__label">С оператором</div>
          <div class="checks">
            <label class="chk"><input type="checkbox"><span>да</span></label>
            <label class="chk"><input type="checkbox"><span>без</span></label>
          </div>
        </div>

        <div>
          <div class="filter__label">Доставка</div>
          <div class="checks">
            <label class="chk"><input type="checkbox"><span>трал</span></label>
            <label class="chk"><input type="checkbox"><span>своим ходом</span></label>
          </div>
        </div>

        <button type="button" class="btn btn-primary" id="applyFilter">Показать результат</button>
      </div>

      <div class="filter__meta">
        <div class="found">найдено: <b id="foundCount">8</b></div>
        <div class="sort" id="sort">
          <button type="button" class="sort__toggle">
            <span class="sort__label">По популярности</span>
            <span class="chev">▾</span>
          </button>
          <div class="sort__menu">
            <button data-sort="popular">По популярности</button>
            <button data-sort="price-asc">Сначала дешёвые</button>
            <button data-sort="price-desc">Сначала дорогие</button>
            <button data-sort="power">По мощности</button>
          </div>
        </div>
      </div>
    </form>

    <div class="grid" id="grid"></div>

    <div class="load-more-wrap">
      <div class="line"></div>
      <button class="load-more" id="loadMore" type="button">Показать еще</button>
    </div>
  </div>
</section>

<!-- ============ Promo slider ============ -->
<section class="promo">
  <div class="swiper" id="promoSwiper">
    <div class="swiper-wrapper">
      <div class="swiper-slide promo__slide" data-ph="Скидка 15%" data-ph-w="1920" data-ph-h="900">
        <div class="promo__info">
          <div class="promo__title">Скидка 15% на аренду от 7 дней</div>
          <a href="#" class="promo__btn">подробнее →</a>
        </div>
      </div>
      <div class="swiper-slide promo__slide" data-ph="Подача по МКАД" data-ph-w="1920" data-ph-h="900">
        <div class="promo__info">
          <div class="promo__title">Подача по МКАД — бесплатно</div>
          <a href="#" class="promo__btn">подробнее →</a>
        </div>
      </div>
    </div>
  </div>
  <button class="arrow" id="ePrev" aria-label="Назад">‹</button>
  <button class="arrow" id="eNext" aria-label="Вперёд">›</button>
</section>

<!-- ============ Portfolio slider ============ -->
<section class="portfolio" id="portfolio">
  <div class="container">
    <h2>Примеры выполненных работ</h2>
  </div>

  <div class="swiper" id="portfolioSwiper">
    <div class="swiper-wrapper" id="portfolioWrapper"></div>
  </div>
  <button class="arrow" id="pPrev" aria-label="Назад">‹</button>
  <button class="arrow" id="pNext" aria-label="Вперёд">›</button>

  <div class="container portfolio__foot">
    <div class="line" style="margin-bottom:28px;"></div>
    <a href="#" class="btn btn-dark">Перейти в портфолио</a>
  </div>
</section>

<!-- ============ About ============ -->
<section class="section section--soft" id="about">
  <div class="container">
    <div class="about">
      <div class="about__media">
        <img src="" data-ph="Парк техники" data-ph-w="1200" data-ph-h="900" alt="Парк техники">
        <div class="about__badge">
          <b>2012</b>
          <span>год основания</span>
        </div>
      </div>
      <div>
        <span class="about__kicker">О компании</span>
        <h2>Своя техника, свои операторы, свои сроки</h2>
        <p>
          «СпецТехПарк» — 60 единиц техники в собственности и штат из 40 машинистов
          с опытом от 5 лет. Мы не перепродаём чужие машины: клиент получает технику
          напрямую от владельца, поэтому мы отвечаем и за сроки, и за качество работ.
        </p>
        <p>
          Работаем на объектах любого масштаба — от частного коттеджа до промышленной
          стройки. Каждая машина застрахована, проходит ТО каждые 250 моточасов.
        </p>

        <div class="about__nums">
          <div><b>480+</b><span>проектов за 3 года</span></div>
          <div><b>98%</b><span>клиентов возвращаются</span></div>
          <div><b>40</b><span>операторов в штате</span></div>
          <div><b>0 ₽</b><span>за подачу в пределах МКАД</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Advantages ============ -->
<section class="section" id="services">
  <div class="container">
    <h2 style="margin-bottom:36px;">Преимущества работы с нами</h2>

    <div class="adv-grid">
      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
        </div>
        <h3>Собственный парк</h3>
        <p>60+ единиц техники без субаренды. Свободная машина есть всегда — проверяем по факту.</p>
      </div>

      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h3>Доставка за 3 часа</h3>
        <p>Тралы и низкорамные платформы в наличии. Подача по Москве — бесплатно.</p>
      </div>

      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2v20M2 12h20"/></svg>
        </div>
        <h3>Работаем 24/7</h3>
        <p>Ночные смены, выходные и праздники — примем заявку в любое время.</p>
      </div>

      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h3>Опытные операторы</h3>
        <p>Машинисты с опытом от 5 лет и допусками. Работают аккуратно и по ТЗ.</p>
      </div>

      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <h3>Страховка техники</h3>
        <p>ОСАГО и КАСКО на каждую единицу. Ответственность перед третьими лицами покрыта.</p>
      </div>

      <div class="adv">
        <div class="adv__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
        <h3>Работаем с НДС</h3>
        <p>Полный пакет документов, безнал, отсрочка для постоянных клиентов.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============ Reviews ============ -->
<section class="section section--soft" id="reviews">
  <div class="container">
    <h2 style="margin-bottom:36px;">Отзывы клиентов</h2>

    <div class="reviews">
      <div class="review">
        <div class="review__head">
          <div class="review__avatar">АК</div>
          <div>
            <div class="review__name">Алексей Кравцов</div>
            <div class="review__role">ГК «МонолитСтрой»</div>
          </div>
        </div>
        <div class="review__stars">★★★★★</div>
        <p class="review__text">
          Брали экскаватор Hitachi на две недели под котлован. Подали точно в срок,
          оператор — с головой, всё сделал аккуратно. По документам тоже без вопросов.
        </p>
      </div>

      <div class="review">
        <div class="review__head">
          <div class="review__avatar">МС</div>
          <div>
            <div class="review__name">Марина Соловьёва</div>
            <div class="review__role">ИЖС, Одинцовский р-н</div>
          </div>
        </div>
        <div class="review__stars">★★★★★</div>
        <p class="review__text">
          Заказывала автокран на перекрытие. Приехали на 40 минут раньше,
          всё подняли за смену. Цена — как договаривались, без «допов» на месте.
        </p>
      </div>

      <div class="review">
        <div class="review__head">
          <div class="review__avatar">ДВ</div>
          <div>
            <div class="review__name">Дмитрий Волков</div>
            <div class="review__role">Дорожная компания «Асфальт-Юг»</div>
          </div>
        </div>
        <div class="review__stars">★★★★★</div>
        <p class="review__text">
          Постоянно берём катки и самосвалы. Ни разу не подвели с подачей —
          для дорожных работ это критично. Работаем уже второй год.
        </p>
      </div>
    </div>

    <div class="logos">
      <div class="logo-item">МОНОЛИТ</div>
      <div class="logo-item">АСФАЛЬТ-ЮГ</div>
      <div class="logo-item">СТРОЙГРАД</div>
      <div class="logo-item">БЕТОН-21</div>
      <div class="logo-item">СУ-14</div>
      <div class="logo-item">ДОРСЕРВИС</div>
    </div>
  </div>
</section>

<!-- ============ SEO text ============ -->
<section class="seo">
  <div class="container">
    <h2>Аренда спецтехники в Москве</h2>
    <div class="seo__body">
      <p><b>СПЕЦТЕХНИКА В АРЕНДУ ПОД КЛЮЧ</b></p>
      <p>
        Наша компания предоставляет в аренду широкий парк строительной и дорожной
        техники для объектов любой сложности. Мы работаем с частными заказчиками,
        строительными подрядчиками и государственными структурами. В наличии —
        экскаваторы, погрузчики, самосвалы, автокраны, автовышки, катки и другая
        техника ведущих мировых производителей.
      </p>
      <p><b>ПОЧЕМУ ВЫГОДНО АРЕНДОВАТЬ ТЕХНИКУ У НАС</b></p>
      <ul>
        <li>Собственный парк без посредников — цены ниже на 15–20%;</li>
        <li>Подача на объект в течение 3 часов после подтверждения заявки;</li>
        <li>Опытные операторы с допусками и стажем от 5 лет;</li>
        <li>Гибкая система скидок при долгосрочной аренде;</li>
        <li>Полный пакет закрывающих документов для юрлиц.</li>
      </ul>
      <p><b>КАК ЗАКАЗАТЬ</b></p>
      <p>
        Оставьте заявку на сайте или позвоните по телефону +7 (495) 123-45-67.
        Менеджер уточнит задачу, подберёт технику и рассчитает стоимость
        с учётом доставки. Работаем круглосуточно, включая выходные и праздники.
      </p>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta">
  <div class="container">
    <h2>Планируете работы на объекте?</h2>
    <div class="cta__grid">
      <div class="cta__card">
        <div class="cta__title">Нужна техника<br>на объект?</div>
        <div class="cta__text">
          Менеджер подберёт машину под задачу и объём работ, посчитает стоимость
          смены с доставкой и зарезервирует технику на нужную дату.
        </div>
        <div class="cta__buttons">
          <button class="btn btn-primary" data-open-form>Записаться на встречу</button>
          <button class="btn btn-ghost-light" data-open-form>Заказать звонок</button>
        </div>
      </div>
      <div class="cta__card">
        <div class="cta__title">Постоянный<br>объём работ?</div>
        <div class="cta__text">
          Для компаний с регулярной потребностью в технике — индивидуальные
          условия, отсрочка платежа и закреплённый менеджер.
        </div>
        <div class="cta__buttons">
          <a href="#" class="btn btn-primary">Узнать условия</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Contacts ============ -->
<section class="contacts" id="contacts">
  <div class="container">
    <h2>Как нас найти</h2>

    <div class="contacts__grid">
      <div class="contacts__list">
        <div class="contacts__item">
          <div class="contacts__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <div class="contacts__label">Адрес</div>
            <div class="contacts__value">Москва, ул. Складочная, д. 6, стр. 3</div>
          </div>
        </div>

        <div class="contacts__item">
          <div class="contacts__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          </div>
          <div>
            <div class="contacts__label">Телефон</div>
            <div class="contacts__value"><a href="tel:+74951234567">+7 (495) 123-45-67</a></div>
          </div>
        </div>

        <div class="contacts__item">
          <div class="contacts__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <div class="contacts__label">E-mail</div>
            <div class="contacts__value"><a href="mailto:info@spectechpark.ru">info@spectechpark.ru</a></div>
          </div>
        </div>

        <div class="contacts__item">
          <div class="contacts__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div>
            <div class="contacts__label">Режим работы</div>
            <div class="contacts__value">Офис: пн–пт 9:00–19:00<br>Приём заявок: круглосуточно</div>
          </div>
        </div>
      </div>

      <div class="map">
        <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=37.575%2C55.793%2C37.615%2C55.815&layer=mapnik&marker=55.804%2C37.595" loading="lazy" title="Карта"></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ============ Form ============ -->
<section class="form-section" id="form">
  <div class="container">
    <div class="form-wrap">
      <div>
        <h2>Рассчитаем стоимость за 15 минут</h2>
        <p class="form-lead">
          Оставьте заявку — менеджер перезвонит, уточнит детали и пришлёт точную
          смету на нужную технику и срок.
        </p>
        <ul>
          <li>Подберём технику под задачу и объём работ</li>
          <li>Посчитаем стоимость смены и доставки</li>
          <li>Зарезервируем машину на нужную дату</li>
        </ul>
      </div>

      <div class="form-card">
        <form id="leadForm" novalidate>
          <h3>Заказать звонок</h3>
          <p class="form-sub">Перезвоним в течение 15 минут</p>

          <label>
            <span>Ваше имя *</span>
            <input type="text" name="name" required placeholder="Иван">
          </label>

          <label>
            <span>Телефон *</span>
            <input type="tel" name="phone" id="phone" required placeholder="+7 (___) ___-__-__">
          </label>

          <label>
            <span>Что нужно</span>
            <select name="equipment">
              <option value="">— выберите технику —</option>
              <option>Экскаватор-погрузчик</option>
              <option>Гусеничный экскаватор</option>
              <option>Самосвал</option>
              <option>Автокран</option>
              <option>Фронтальный погрузчик</option>
              <option>Автовышка</option>
              <option>Каток дорожный</option>
              <option>Другое / консультация</option>
            </select>
          </label>

          <label>
            <span>Комментарий</span>
            <textarea name="comment" placeholder="Адрес объекта, срок, объём работ"></textarea>
          </label>

          <button type="submit" class="btn btn-primary btn-block">Отправить заявку</button>
          <p class="agree">Нажимая кнопку, вы соглашаетесь с <a href="#">политикой обработки персональных данных</a>.</p>
        </form>

        <div class="form-success" id="formSuccess">
          <div class="form-success__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h3>Заявка отправлена</h3>
          <p style="color:var(--muted);margin-top:8px;">Менеджер свяжется с вами в течение 15 минут.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Footer ============ -->
<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <div>
        <h4>Каталог техники</h4>
        <ul>
          <li><a href="#equipment">Земляные работы</a></li>
          <li><a href="#equipment">Подъём и краны</a></li>
          <li><a href="#equipment">Дорожные работы</a></li>
          <li><a href="#equipment">Грузоперевозки</a></li>
        </ul>
      </div>

      <div>
        <h4>Услуги</h4>
        <ul>
          <li><a href="#">Аренда с оператором</a></li>
          <li><a href="#">Аренда без оператора</a></li>
          <li><a href="#">Доставка на объект</a></li>
          <li><a href="#">Разработка грунта</a></li>
          <li><a href="#">Демонтажные работы</a></li>
        </ul>
      </div>

      <div>
        <h4>О компании</h4>
        <ul>
          <li><a href="#about">О нас</a></li>
          <li><a href="#portfolio">Портфолио</a></li>
          <li><a href="#reviews">Отзывы</a></li>
          <li><a href="#">Кредитование</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
      </div>
    </div>

    <div class="footer__contacts">
      <div class="footer__contact">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>Москва, ул. Складочная, 6с3</span>
      </div>
      <div class="footer__contact">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <a href="tel:+74951234567">+7 (495) 123-45-67</a>
      </div>
      <div class="footer__contact">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <a href="mailto:info@spectechpark.ru">info@spectechpark.ru</a>
      </div>
    </div>

    <div class="footer__bottom">
      <div>© 2012–2026 ООО «СпецТехПарк». Все права защищены.</div>
      <form class="search" onsubmit="return false">
        <input type="text" placeholder="Поиск по сайту">
        <button type="submit" aria-label="Найти"></button>
      </form>
    </div>
  </div>
</footer>

<!-- ============ Scripts ============ -->
<script>
/* =========================================================
   1. SVG-заглушки
   ========================================================= */
function escXml(s){
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function wrapText(text, maxChars){
  const words = String(text).split(/\s+/);
  const lines = [];
  let cur = '';
  words.forEach(function(w){
    if(cur && (cur + ' ' + w).length > maxChars){ lines.push(cur); cur = w; }
    else { cur = cur ? cur + ' ' + w : w; }
  });
  if(cur) lines.push(cur);
  return lines.slice(0, 3);
}
function ph(label, w, h){
  w = w || 900; h = h || 520;
  const fs = Math.min(48, Math.max(14, Math.round(Math.min(w, h) / 14)));
  const lh = fs * 1.2;
  const maxChars = Math.max(8, Math.round((w * 0.82) / (fs * 0.58)));
  const lines = wrapText(label, maxChars);
  const startY = h / 2 - (lines.length - 1) * lh / 2;
  const tspans = lines.map(function(l, i){
    return '<tspan x="50%" y="' + (startY + i * lh) + '">' + escXml(l) + '</tspan>';
  }).join('');
  const svg =
    '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '">' +
      '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">' +
        '<stop offset="0" stop-color="#16303f"/>' +
        '<stop offset="1" stop-color="#0b1720"/>' +
      '</linearGradient></defs>' +
      '<rect width="100%" height="100%" fill="url(#g)"/>' +
      '<circle cx="' + (w * 0.5) + '" cy="' + (h * 0.5) + '" r="' + (Math.min(w, h) * 0.32) + '" ' +
        'fill="none" stroke="#5abcdc" stroke-width="' + Math.max(2, fs / 8) + '" opacity="0.18"/>' +
      '<text fill="#dfe9f0" font-family="Arial, Helvetica, sans-serif" font-size="' + fs + '" ' +
        'font-weight="700" text-anchor="middle" dominant-baseline="middle">' + tspans + '</text>' +
    '</svg>';
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}
function applyPlaceholders(root){
  (root || document).querySelectorAll('[data-ph]').forEach(function(el){
    const w = parseInt(el.getAttribute('data-ph-w'), 10) || 900;
    const h = parseInt(el.getAttribute('data-ph-h'), 10) || 520;
    const uri = ph(el.getAttribute('data-ph'), w, h);
    if(el.tagName === 'IMG'){ el.src = uri; }
    else { el.style.backgroundImage = 'url("' + uri + '")'; }
  });
}

/* =========================================================
   2. Слайдер с автопрокруткой
   ========================================================= */
function createSlider(selector, opts){
  opts = opts || {};
  const root = document.querySelector(selector);
  if(!root) return null;
  const wrapper = root.querySelector('.swiper-wrapper');
  if(!wrapper) return null;
  const originals = Array.prototype.slice.call(wrapper.children);
  if(!originals.length) return null;

  const loop      = !!opts.loop;
  const gap       = opts.spaceBetween || 0;
  const basePV    = opts.perView || 1;
  const bps       = opts.breakpoints || null;
  const duration  = opts.duration || 420;
  const nav       = opts.nav || {};
  const prevBtn   = nav.prev ? document.querySelector(nav.prev) : null;
  const nextBtn   = nav.next ? document.querySelector(nav.next) : null;

  const autoplayDelay = opts.autoplay
      ? (typeof opts.autoplay === 'number' ? opts.autoplay : 4500)
      : 0;
  let autoplayTimer = null;
  let autoplayPaused = false;

  let slides = originals.slice();
  let index  = 0;
  let perView = basePV;
  let slideW = 0;
  let resetTimer = null;

  if(loop){
    const firstClone = originals[0].cloneNode(true);
    const lastClone  = originals[originals.length - 1].cloneNode(true);
    wrapper.appendChild(firstClone);
    wrapper.insertBefore(lastClone, wrapper.firstChild);
    slides = Array.prototype.slice.call(wrapper.children);
    index = 1;
  }

  function calcPerView(){
    let pv = basePV;
    if(bps){
      Object.keys(bps).map(Number).sort(function(a,b){return a-b;}).forEach(function(bp){
        if(window.innerWidth >= bp) pv = bps[bp];
      });
    }
    return pv;
  }

  function getAvailW(){
    const cs = window.getComputedStyle(root);
    const pl = parseFloat(cs.paddingLeft)  || 0;
    const pr = parseFloat(cs.paddingRight) || 0;
    return Math.max(0, root.clientWidth - pl - pr);
  }

  function layout(){
    perView = calcPerView();
    const availW = getAvailW();
    slideW = (availW - gap * (perView - 1)) / perView;
    slides.forEach(function(s, i){
      s.style.width       = slideW + 'px';
      s.style.marginRight = (i === slides.length - 1 ? 0 : gap) + 'px';
      s.style.flex        = '0 0 auto';
    });
    apply(false);
  }

  function apply(animate){
    if(animate === false){
      wrapper.style.transition = 'none';
    } else {
      wrapper.style.transition = 'transform ' + duration + 'ms ease';
    }
    const offset = index * (slideW + gap);
    wrapper.style.transform = 'translate3d(' + (-offset) + 'px, 0, 0)';
    if(animate === false){
      void wrapper.offsetWidth;
      wrapper.style.transition = 'transform ' + duration + 'ms ease';
    }
    updateNav();
  }

  function maxIndex(){ return Math.max(0, slides.length - perView); }

  function updateNav(){
    if(prevBtn) prevBtn.classList.toggle('swiper-button-disabled', !loop && index <= 0);
    if(nextBtn) nextBtn.classList.toggle('swiper-button-disabled', !loop && index >= maxIndex());
  }

  function goTo(i, animate){
    if(loop){
      if(i < 0) i = 0;
      if(i > slides.length - 1) i = slides.length - 1;
    } else {
      if(i < 0) i = 0;
      if(i > maxIndex()) i = maxIndex();
    }
    index = i;
    apply(animate !== false);

    if(loop){
      clearTimeout(resetTimer);
      if(index === slides.length - 1){
        resetTimer = setTimeout(function(){ goTo(1, false); }, duration + 20);
      } else if(index === 0){
        resetTimer = setTimeout(function(){ goTo(slides.length - 2, false); }, duration + 20);
      }
    }
  }

  function next(){ goTo(index + 1); }
  function prev(){ goTo(index - 1); }

  if(nextBtn) nextBtn.addEventListener('click', function(){ next(); restartAutoplay(); });
  if(prevBtn) prevBtn.addEventListener('click', function(){ prev(); restartAutoplay(); });

  function startAutoplay(){
    if(!autoplayDelay) return;
    stopAutoplay();
    autoplayTimer = setInterval(function(){
      if(!autoplayPaused && !document.hidden) next();
    }, autoplayDelay);
  }
  function stopAutoplay(){
    if(autoplayTimer){ clearInterval(autoplayTimer); autoplayTimer = null; }
  }
  function restartAutoplay(){
    if(!autoplayDelay) return;
    startAutoplay();
  }

  root.addEventListener('mouseenter', function(){ autoplayPaused = true; });
  root.addEventListener('mouseleave', function(){ autoplayPaused = false; });

  document.addEventListener('visibilitychange', function(){
    if(document.hidden) stopAutoplay();
    else startAutoplay();
  });

  let rt = null;
  window.addEventListener('resize', function(){
    clearTimeout(rt);
    rt = setTimeout(layout, 150);
  });

  layout();
  startAutoplay();

  return { next: next, prev: prev, goTo: goTo, layout: layout };
}

/* =========================================================
   3. Двойной слайдер-фильтр
   ========================================================= */
function createDualRange(el, opts){
  if(!el) return null;
  const min  = opts.min;
  const max  = opts.max;
  const step = opts.step || 1;
  const v    = [opts.values[0], opts.values[1]];

  el.classList.add('dr');
  el.innerHTML =
    '<div class="dr__track">' +
      '<div class="dr__fill"></div>' +
      '<div class="dr__handle" data-i="0"></div>' +
      '<div class="dr__handle" data-i="1"></div>' +
    '</div>';

  const track   = el.querySelector('.dr__track');
  const fill    = el.querySelector('.dr__fill');
  const handles = [
    el.querySelector('[data-i="0"]'),
    el.querySelector('[data-i="1"]')
  ];

  function pct(val){ return (val - min) / (max - min) * 100; }

  function render(){
    handles[0].style.left = pct(v[0]) + '%';
    handles[1].style.left = pct(v[1]) + '%';
    fill.style.left  = pct(v[0]) + '%';
    fill.style.right = (100 - pct(v[1])) + '%';
    if(opts.onChange) opts.onChange(v[0], v[1]);
  }

  function valueFromX(clientX){
    const r = track.getBoundingClientRect();
    let p = r.width ? (clientX - r.left) / r.width : 0;
    p = Math.max(0, Math.min(1, p));
    let val = min + p * (max - min);
    val = Math.round(val / step) * step;
    return Math.max(min, Math.min(max, val));
  }

  handles.forEach(function(h, i){
    h.addEventListener('pointerdown', function(e){
      e.preventDefault();
      h.setPointerCapture(e.pointerId);

      function move(ev){
        let val = valueFromX(ev.clientX);
        if(i === 0) val = Math.min(val, v[1]);
        else        val = Math.max(val, v[0]);
        v[i] = val;
        render();
      }
      function up(){
        h.removeEventListener('pointermove', move);
        h.removeEventListener('pointerup', up);
        h.removeEventListener('pointercancel', up);
      }
      h.addEventListener('pointermove', move);
      h.addEventListener('pointerup', up);
      h.addEventListener('pointercancel', up);
    });
  });

  render();
  return { get: function(){ return v.slice(); } };
}

/* =========================================================
   4. Данные
   ========================================================= */
const EQUIPMENT = [
  {cat:'earth', tag:'Земляные работы', name:'Экскаватор-погрузчик JCB 3CX',
   weight:8,  price:14, power:91,
   specs:[['1,0 м³','ковш'],['5,4 м','глубина'],['8 т','масса']]},
  {cat:'earth', tag:'Земляные работы', name:'Гусеничный экскаватор Hitachi ZX200',
   weight:20, price:18, power:122,
   specs:[['1,2 м³','ковш'],['6,7 м','глубина'],['20 т','масса']]},
  {cat:'lift', tag:'Подъём и краны', name:'Автокран Liebherr LTM 1050',
   weight:30, price:24, power:270,
   specs:[['50 т','грузоподъём.'],['38 м','стрела'],['32 м','вылет']]},
  {cat:'truck', tag:'Грузоперевозки', name:'Самосвал Volvo FMX 20 м³',
   weight:25, price:16, power:420,
   specs:[['20 м³','объём'],['25 т','грузоподъём.'],['6×4','колёса']]},
  {cat:'earth', tag:'Земляные работы', name:'Фронтальный погрузчик SDLG LG936',
   weight:3,  price:13, power:92,
   specs:[['1,8 м³','ковш'],['3 т','грузоподъём.'],['2,9 м','выгрузка']]},
  {cat:'lift', tag:'Подъём и краны', name:'Автовышка 22 м',
   weight:5,  price:12, power:120,
   specs:[['22 м','высота'],['14 м','вылет'],['250 кг','люлька']]},
  {cat:'road', tag:'Дорожные работы', name:'Каток дорожный Bomag BW 138',
   weight:4,  price:11, power:45,
   specs:[['4 т','масса'],['1,4 м','ширина'],['да','вибро']]},
  {cat:'truck', tag:'Грузоперевозки', name:'Манипулятор 10 т',
   weight:10, price:14, power:280,
   specs:[['5 т','борт'],['10 т','кран'],['8 м','стрела']]}
];

const PORTFOLIO = [
  {name:'ЖК «Северный парк»', area:'1200 м²'},
  {name:'Коттедж, Одинцово',  area:'320 м²'},
  {name:'Склад, Домодедово',  area:'2800 м²'},
  {name:'Дорога, Симферополь',area:'2,4 км'},
  {name:'Демонтаж, Химки',    area:'560 м²'},
  {name:'Фундамент, Истра',   area:'180 м²'}
];

/* =========================================================
   5. Каталог
   ========================================================= */
const grid = document.getElementById('grid');
let visibleCount = 6;
let sortMode = 'popular';
let rangeWeight = [1, 50];
let rangePrice  = [10, 25];

function fmtPrice(v){ return v + ' 000 ₽'; }

function getFilteredList(){
  const cats = Array.prototype.slice
    .call(document.querySelectorAll('.chk input[value]:checked'))
    .map(function(i){ return i.value; });

  let list = EQUIPMENT.slice();

  if(cats.length) list = list.filter(function(e){ return cats.indexOf(e.cat) !== -1; });
  list = list.filter(function(e){
    return e.weight >= rangeWeight[0] && e.weight <= rangeWeight[1];
  });
  list = list.filter(function(e){
    return e.price >= rangePrice[0] && e.price <= rangePrice[1];
  });

  if(sortMode === 'price-asc')  list.sort(function(a,b){ return a.price - b.price; });
  if(sortMode === 'price-desc') list.sort(function(a,b){ return b.price - a.price; });
  if(sortMode === 'power')      list.sort(function(a,b){ return b.power - a.power; });

  return list;
}

function renderGrid(){
  const list  = getFilteredList();
  const shown = list.slice(0, visibleCount);

  grid.innerHTML = shown.map(function(e){
    const img = ph(e.name, 900, 520);
    return '' +
    '<article class="card">' +
      '<div class="card__img">' +
        '<span class="card__tag">' + e.tag + '</span>' +
        '<img src="' + img + '" alt="' + e.name + '" loading="lazy">' +
      '</div>' +
      '<div class="card__body">' +
        '<h3 class="card__title">' + e.name + '</h3>' +
        '<div class="card__specs">' +
          e.specs.map(function(s){
            return '<div><b>' + s[0] + '</b><span>' + s[1] + '</span></div>';
          }).join('') +
        '</div>' +
        '<div class="card__foot">' +
          '<div class="card__price">' + fmtPrice(e.price) + '<small>за смену 8 ч</small></div>' +
          '<button class="btn btn-primary btn--sm" data-open-form>Заказать</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }).join('');

  document.getElementById('foundCount').textContent = list.length;

  const lm = document.getElementById('loadMore');
  lm.style.display = list.length > visibleCount ? '' : 'none';
}

document.querySelectorAll('.chk input[value]').forEach(function(input){
  input.addEventListener('change', function(){
    visibleCount = 6;
    renderGrid();
  });
});

document.getElementById('loadMore').addEventListener('click', function(){
  visibleCount += 6;
  renderGrid();
});

const sortEl = document.getElementById('sort');
sortEl.querySelector('.sort__toggle').addEventListener('click', function(e){
  e.stopPropagation();
  sortEl.classList.toggle('open');
});
document.addEventListener('click', function(){ sortEl.classList.remove('open'); });
sortEl.querySelectorAll('.sort__menu button').forEach(function(btn){
  btn.addEventListener('click', function(){
    sortEl.querySelector('.sort__label').textContent = btn.textContent;
    sortMode = btn.getAttribute('data-sort');
    sortEl.classList.remove('open');
    renderGrid();
  });
});

try {
  createDualRange(document.getElementById('sliderWeight'), {
    min:1, max:50, step:1, values:[1,50],
    onChange:function(a, b){
      rangeWeight = [a, b];
      document.querySelector('[data-min="weight"]').textContent = a;
      document.querySelector('[data-max="weight"]').textContent = b;
    }
  });

  createDualRange(document.getElementById('sliderPrice'), {
    min:10, max:25, step:0.5, values:[10,25],
    onChange:function(a, b){
      rangePrice = [a, b];
      document.querySelector('[data-min="price"]').textContent = a.toFixed(1);
      document.querySelector('[data-max="price"]').textContent = b.toFixed(1);
    }
  });
} catch(e){
  console.warn('Фильтр диапазонов не инициализирован:', e);
}

document.getElementById('applyFilter').addEventListener('click', function(){
  visibleCount = 6;
  renderGrid();
  document.getElementById('equipment').scrollIntoView({behavior:'smooth'});
});

/* =========================================================
   6. Портфолио + слайдеры (с автопрокруткой)
   ========================================================= */
document.getElementById('portfolioWrapper').innerHTML = PORTFOLIO.map(function(p){
  const img = ph(p.name, 700, 525);
  return '' +
  '<div class="swiper-slide">' +
    '<a href="#" class="pf-card">' +
      '<div class="pf-card__img">' +
        '<img src="' + img + '" alt="' + p.name + '" loading="lazy">' +
      '</div>' +
      '<div class="pf-card__name">' + p.name + '</div>' +
      '<div class="pf-card__meta">' + p.area + '</div>' +
    '</a>' +
  '</div>';
}).join('');

try {
  createSlider('#heroSwiper', {
    loop: true,
    autoplay: 5000,
    nav: { next: '#hNext', prev: '#hPrev' }
  });

  createSlider('#promoSwiper', {
    loop: true,
    autoplay: 6000,
    nav: { next: '#eNext', prev: '#ePrev' }
  });

  createSlider('#portfolioSwiper', {
    perView: 1,
    spaceBetween: 24,
    autoplay: 7000,
    nav: { next: '#pNext', prev: '#pPrev' },
    breakpoints: { 600: 2, 900: 3, 1200: 4 }
  });
} catch(e){
  console.warn('Слайдеры не инициализированы:', e);
}

/* =========================================================
   7. NAV-main-menu: toggle на мобилке + dropdown
   ========================================================= */
(function(){
  const nav      = document.getElementById('nav-main-menu');
  const backdrop = document.getElementById('backdrop');
  const burger   = document.getElementById('burger');

  function openNav(){
    nav.classList.add('active');
    backdrop.classList.add('active');
    nav.setAttribute('aria-hidden','false');
    burger.setAttribute('aria-expanded','true');
    document.body.style.overflow = 'hidden';
  }
  function closeNav(){
    nav.classList.remove('active');
    backdrop.classList.remove('active');
    nav.setAttribute('aria-hidden','true');
    burger.setAttribute('aria-expanded','false');
    document.body.style.overflow = '';
  }

  burger.addEventListener('click', function(){
    if(nav.classList.contains('active')) closeNav();
    else openNav();
  });

  backdrop.addEventListener('click', closeNav);

  // Закрываем меню при клике по ссылке на мобилке
  nav.querySelectorAll('[data-close]').forEach(function(a){
    a.addEventListener('click', function(){
      if(window.innerWidth <= 1024) closeNav();
    });
  });

  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeNav();
  });

  // Дропдаун «Доставка техники»
  const dd       = document.getElementById('navDropdown');
  const hidden   = document.getElementById('navHiddenMenu');
  if(dd && hidden){
    dd.addEventListener('click', function(){
      dd.classList.toggle('active');
      hidden.classList.toggle('active');
    });
  }
})();

/* =========================================================
   8. Кнопки «Заказать»
   ========================================================= */
document.addEventListener('click', function(e){
  const btn = e.target.closest('[data-open-form]');
  if(!btn) return;
  const nav = document.getElementById('nav-main-menu');
  if(nav.classList.contains('active')){
    nav.classList.remove('active');
    document.getElementById('backdrop').classList.remove('active');
    document.body.style.overflow = '';
  }
  document.getElementById('form').scrollIntoView({behavior:'smooth'});
  setTimeout(function(){
    const f = document.querySelector('#leadForm input[name="name"]');
    if(f) f.focus();
  }, 500);
});

/* =========================================================
   9. Маска телефона + отправка формы
   ========================================================= */
(function(){
  const phone = document.getElementById('phone');
  if(!phone) return;

  phone.addEventListener('input', function(e){
    let v = e.target.value.replace(/\D/g,'');
    if(v.startsWith('8')) v = '7' + v.slice(1);
    if(!v.startsWith('7')) v = '7' + v;
    v = v.slice(0, 11);
    let out = '+7';
    if(v.length > 1)  out += ' (' + v.slice(1,4);
    if(v.length >= 5) out += ') ' + v.slice(4,7);
    if(v.length >= 8) out += '-' + v.slice(7,9);
    if(v.length >= 10) out += '-' + v.slice(9,11);
    e.target.value = out;
  });

  const form = document.getElementById('leadForm');
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form));
    if(!data.name.trim() || String(data.phone).length < 18){
      form.querySelector('[name="phone"]').style.borderColor = '#e11d48';
      return;
    }
    console.log('LEAD:', data);
    form.style.display = 'none';
    document.getElementById('formSuccess').classList.add('show');
  });
})();

/* =========================================================
   10. Инициализация
   ========================================================= */
applyPlaceholders();
renderGrid();
</script>
</body>
</html>