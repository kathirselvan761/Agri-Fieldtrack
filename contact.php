<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
checkLoggedIn();

$pageTitle = __('contact_us', 'தொடர்பு கொள்ள - ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்');
require_once __DIR__ . '/includes/header.php';

// Owner Phone Numbers
$contacts = [
    ['phone' => '99653 18540', 'raw' => '9965318540', 'name' => __('company_name', 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'), 'tag' => 'முதன்மையாளர்'],
    ['phone' => '94887 01535', 'raw' => '9488701535', 'name' => __('company_name', 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'), 'tag' => 'வாடகை தொடர்பு'],
    ['phone' => '63811 59595', 'raw' => '6381159595', 'name' => __('company_name', 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'), 'tag' => 'விவசாய உழவு'],
    ['phone' => '78450 51535', 'raw' => '7845051535', 'name' => __('company_name', 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'), 'tag' => 'உடனடி சேவை']
];

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $service = $_POST['service'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($mobile)) {
        // Log contact message as note/activity or flash success
        setFlash('success', __('saved_success', 'உங்கள் சேவை கோரிக்கை வெற்றிகரமாக பதிவு செய்யப்பட்டது! விரைவில் தொடர்பு கொள்வோம்.'));
        header("Location: contact.php");
        exit();
    } else {
        setFlash('danger', __('error_occurred', 'தயவுசெய்து பெயர் மற்றும் தொலைபேசி எண்ணை உள்ளிடவும்.'));
    }
}
?>

<!-- 1. Tamil Advertisement Banner Hero Section -->
<div class="banner-hero mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8">
      <span class="banner-badge mb-2"><i class="fa-solid fa-tractor me-1"></i> <?php echo __('tractor_rental_erp', 'விவசாய சேவை மையம் & டிராக்டர் வாடகை'); ?></span>
      <h1 class="fw-extrabold display-5 mb-2 text-warning brand-font"><?php echo __('contact_banner_title', 'ஸ்ரீ அங்காளபரமேஸ்வரி டிராக்டர்ஸ்'); ?></h1>
      <p class="lead text-light mb-3" style="max-width: 650px;">
        <?php echo __('contact_sub', 'டிராக்டர் வாடகை, ரோட்டவேட்டர், உழவு இயந்திரம், நிலம் சமன் செய்தல் மற்றும் அனைத்து விவசாய சேவைக்கும் தொடர்பு கொள்ளவும்.'); ?>
      </p>
      <div class="d-flex flex-wrap gap-2 pt-1">
        <span class="badge bg-light text-dark px-3 py-2 fs-7 fw-semibold"><i class="bi bi-patch-check-fill text-success me-1"></i> தரமான உழவு</span>
        <span class="badge bg-light text-dark px-3 py-2 fs-7 fw-semibold"><i class="bi bi-currency-rupee text-primary me-1"></i> நியாயமான வாடகை</span>
        <span class="badge bg-light text-dark px-3 py-2 fs-7 fw-semibold"><i class="bi bi-person-workspace text-warning me-1"></i> அனுபவமிக்க ஓட்டுநர்கள்</span>
      </div>
    </div>
    <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
      <div class="bg-white text-dark p-3 rounded-4 shadow-lg d-inline-block text-center" style="max-width: 280px; border-bottom: 4px solid #10b981;">
        <i class="fa-solid fa-tractor text-success mb-2" style="font-size: 3rem;"></i>
        <h6 class="fw-bold mb-1">உடனடி உழவு தேவைக்கு</h6>
        <p class="small text-muted mb-2">24/7 தொடர்பு கொள்ளவும்</p>
        <a href="tel:9965318540" class="btn btn-success btn-sm w-100 fw-bold shadow-sm"><i class="bi bi-telephone-fill me-1"></i> 99653 18540</a>
      </div>
    </div>
  </div>
</div>

<!-- 2. Owner Highlighted Contact Cards Section -->
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold brand-font text-primary mb-0"><i class="bi bi-phone-vcard me-2"></i><?php echo __('owner_contacts', 'உரிமையாளர் தொடர்பு எண்கள்'); ?></h4>
    <small class="text-muted">எப்போது வேண்டுமானாலும் தொடர்பு கொள்ளலாம்</small>
  </div>

  <div class="row g-3">
    <?php foreach ($contacts as $index => $c): ?>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="contact-card-phone text-center h-100">
          <div class="bg-success-light text-success rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 54px; height: 54px;">
            <i class="bi bi-telephone-outbound-fill fs-4"></i>
          </div>
          <span class="badge bg-light text-success border border-success-subtle mb-2 px-2 py-1"><?php echo $c['tag']; ?></span>
          <h4 class="fw-bold text-dark mb-3"><?php echo $c['phone']; ?></h4>
          
          <div class="d-grid gap-2">
            <a href="tel:<?php echo $c['raw']; ?>" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center gap-1 shadow-sm">
              <i class="bi bi-telephone-fill"></i> <?php echo __('call_now', 'உடனே அழைக்கவும்'); ?>
            </a>
            <a href="https://wa.me/91<?php echo $c['raw']; ?>?text=<?php echo urlencode('வணக்கம், டிராக்டர் உழவு சேவை தேவைப்படுகிறது.'); ?>" target="_blank" class="btn btn-whatsapp btn-sm d-flex align-items-center justify-content-center gap-1">
              <i class="bi bi-whatsapp"></i> <?php echo __('whatsapp_now', 'வாட்ஸ்அப் மெசேஜ்'); ?>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 3. Agricultural Services Offered Section -->
<div class="mb-4">
  <h4 class="fw-bold brand-font text-success mb-3"><i class="bi bi-tools me-2"></i><?php echo __('our_services', 'எங்கள் விவசாய சேவைகள்'); ?></h4>
  
  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap">
          <i class="fa-solid fa-tractor"></i>
        </div>
        <h5 class="fw-bold mb-2">ரோட்டவேட்டர் உழவு</h5>
        <p class="text-muted small mb-0">நிலத்தை மென்மையாக்கவும், கட்டிகளை உடைத்து மண் வளம் பெருக்கவும் ரோட்டவேட்டர் சேவைகள் வழங்கப்படுகின்றன.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap" style="background: rgba(43, 127, 255, 0.12); color: #2b7fff;">
          <i class="bi bi-gear-wide-connected"></i>
        </div>
        <h5 class="fw-bold mb-2">கொத்துக்கலப்பை & சட்டி உழவு</h5>
        <p class="text-muted small mb-0">ஆழமான உழவு, புதிய நிலம் சீரமைத்தல் மற்றும் 2 & 3 பாட்டம் சட்டி உழவு இயந்திரங்கள் கிடைக்கின்றன.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
          <i class="bi bi-bounding-box-circles"></i>
        </div>
        <h5 class="fw-bold mb-2">நிலம் சமன் செய்தல் & லேசர் கிரேடர்</h5>
        <p class="text-muted small mb-0">நீர் சமமாக பாயவும், மண் சீராக அமையவும் லேசர் லெவெல்லர் மற்றும் லெவெல்லிங் போர்டு உழவு.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap" style="background: rgba(239, 68, 68, 0.12); color: #ef4444;">
          <i class="bi bi-tree-fill"></i>
        </div>
        <h5 class="fw-bold mb-2">வாழைக்குழி & குழி தோண்டுதல்</h5>
        <p class="text-muted small mb-0">வாழை, தென்னை மற்றும் மரக்கன்றுகள் நட குழி தோண்டும் பிரத்யேக டிராக்டர் இயந்திர சேவைகள்.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap" style="background: rgba(6, 182, 212, 0.12); color: #06b6d4;">
          <i class="bi bi-truck"></i>
        </div>
        <h5 class="fw-bold mb-2">டிரெய்லர் பாரம் ஏற்றி செல்லுதல்</h5>
        <p class="text-muted small mb-0">எரு, மணல், கல், விவசாய கழிவுகள் மற்றும் விவசாய பொருட்களை கொண்டு செல்ல டிரெய்லர் ஏற்றி செல்லும் சேவை.</p>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="service-box">
        <div class="service-icon-wrap" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
          <i class="bi bi-grid-3x3-gap-fill"></i>
        </div>
        <h5 class="fw-bold mb-2">களையெடுத்தல் & டிஸ்க் ஹேர்ரோ</h5>
        <p class="text-muted small mb-0">பயிர் உழவு, களை எடுத்தல், பம்பாய்க்கலப்பை மற்றும் டிஸ்க் ஹேர்ரோ மூலம் உழவு சேவைகள்.</p>
      </div>
    </div>
  </div>
</div>

<!-- 4. Contact Form & Google Location Map Section -->
<div class="row g-4 mb-4">
  <!-- Inquiry Form -->
  <div class="col-12 col-lg-6">
    <div class="card-erp p-4 h-100">
      <h5 class="fw-bold brand-font text-primary mb-3"><i class="bi bi-envelope-paper-fill me-2"></i><?php echo __('contact_form_title', 'சேவை தேவைக்கு பதிவு செய்யவும்'); ?></h5>
      
      <form action="contact.php" method="POST">
        <input type="hidden" name="send_inquiry" value="1">

        <div class="mb-3">
          <label class="form-label fw-semibold">உங்கள் பெயர் *</label>
          <input type="text" name="name" class="form-control" required placeholder="எ.கா. கார்த்திக் ராஜா">
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">கைபேசி எண் *</label>
            <input type="tel" name="mobile" class="form-control" required placeholder="எ.கா. 9965318540">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">ஊர் / கிராமம் *</label>
            <input type="text" name="village" class="form-control" required placeholder="எ.கா. வடுகபாளையம்">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold"><?php echo __('required_service', 'தேவையான சேவை'); ?> *</label>
          <select name="service" class="form-select" required>
            <option value="Rotavator">ரோட்டவேட்டர் உழவு (Rotavator)</option>
            <option value="Plough">கொத்துக்கலப்பை / சட்டி உழவு (Ploughing)</option>
            <option value="Levelling">நிலம் சமன் செய்தல் (Land Levelling)</option>
            <option value="Pitting">வாழைக்குழி தோண்டுதல் (Banana Pitting)</option>
            <option value="Haulage">டிரெய்லர் ஏற்றி செல்லுதல் (Trailer Haulage)</option>
            <option value="General">இதர விவசாய சேவை (Other Services)</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">கூடுதல் விவரங்கள் / செய்தி</label>
          <textarea name="message" class="form-control" rows="3" placeholder="ஏக்கர் அளவு, தேதி அல்லது நில விவரங்களை உள்ளிடவும்..."></textarea>
        </div>

        <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm"><i class="bi bi-send-fill me-1"></i> பதிவை அனுப்புங்கள்</button>
      </form>
    </div>
  </div>

  <!-- Location Map & Address -->
  <div class="col-12 col-lg-6">
    <div class="card-erp p-4 h-100 d-flex flex-column justify-content-between">
      <div>
        <h5 class="fw-bold brand-font text-success mb-3"><i class="bi bi-geo-alt-fill me-2"></i><?php echo __('location_map', 'எங்கள் முகவரி & இருப்பிடம்'); ?></h5>
        
        <div class="p-3 bg-light rounded-3 mb-3 border">
          <h6 class="fw-bold text-dark mb-1"><i class="bi bi-building me-1 text-primary"></i> श्री अंगாளபரமேஸ்வரி டிராக்டர்ஸ்</h6>
          <p class="text-muted small mb-2"><i class="bi bi-geo me-1"></i> பிரதான சாலை, வடுகபாளையம், கோயம்புத்தூர் / திருப்பூர் மாவட்டம், தமிழ்நாடு - 641664</p>
          <div class="d-flex flex-wrap gap-2 small">
            <span><i class="bi bi-telephone text-success me-1"></i> 99653 18540</span>
            <span><i class="bi bi-whatsapp text-success me-1"></i> 94887 01535</span>
          </div>
        </div>
      </div>

      <!-- Google Maps Responsive Embed -->
      <div class="rounded-3 overflow-hidden border shadow-sm flex-grow-1" style="min-height: 250px;">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15664.21882894568!2d77.2000!3d11.0000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba906f3e0987654%3A0x123456789abcdef!2sTamil%20Nadu!5e0!3m2!1sta!2sin!4v1700000000000!5m2!1sta!2sin" 
          width="100%" 
          height="100%" 
          style="border:0; min-height: 250px;" 
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>
  </div>
</div>

<!-- 5. Footer Quick Contact Bar -->
<div class="card-erp p-3 text-center bg-primary-light border-primary-subtle mb-4">
  <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
    <span class="fw-bold text-primary"><i class="bi bi-headset me-1"></i> அவசர உழவு சேவைக்கு அலைபேசியில் தொடர்பு கொள்ளவும்:</span>
    <a href="tel:9965318540" class="badge bg-primary fs-6 text-decoration-none px-3 py-2"><i class="bi bi-telephone-fill me-1"></i> 99653 18540</a>
    <a href="tel:9488701535" class="badge bg-success fs-6 text-decoration-none px-3 py-2"><i class="bi bi-telephone-fill me-1"></i> 94887 01535</a>
    <a href="tel:6381159595" class="badge bg-info fs-6 text-decoration-none px-3 py-2"><i class="bi bi-telephone-fill me-1"></i> 63811 59595</a>
    <a href="tel:7845051535" class="badge bg-warning text-dark fs-6 text-decoration-none px-3 py-2"><i class="bi bi-telephone-fill me-1"></i> 78450 51535</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
