/* ==========================================================================
   CHNStream — Arayüz etkileşimleri
   Saf JavaScript · Bağımlılık yok
   ========================================================================== */

(function () {
    'use strict';

    const body = document.body;
    const root = document.documentElement;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ----------------------------------------------------------------------
       Koyu / açık tema
       ------------------------------------------------------------------- */
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const themeMeta = document.querySelector('meta[name="theme-color"]');

    const syncThemeUI = () => {
        const acikTema = root.getAttribute('data-theme') === 'light';

        if (themeToggle) {
            themeToggle.setAttribute('aria-label', acikTema ? 'Koyu temaya geç' : 'Açık temaya geç');
            themeToggle.setAttribute('title', acikTema ? 'Koyu temaya geç' : 'Açık temaya geç');
        }

        if (themeMeta) {
            themeMeta.setAttribute('content', acikTema ? '#f3ece1' : '#08090d');
        }
    };

    if (themeToggle) {
        syncThemeUI();

        themeToggle.addEventListener('click', () => {
            const yeniTema = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            root.setAttribute('data-theme', yeniTema);

            try {
                window.localStorage.setItem('chnstream-theme', yeniTema);
            } catch (error) {
                /* localStorage kapalıysa tema yine de bu sayfa için değişir. */
            }

            syncThemeUI();
        });
    }

    /* ----------------------------------------------------------------------
       Mobil menü
       ------------------------------------------------------------------- */
    const navToggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');

    const setNav = (open) => {
        body.classList.toggle('nav-open', open);
        if (navToggle) {
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
            const icon = navToggle.querySelector('i');
            if (icon) {
                icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            }
        }
    };

    if (navToggle && nav) {
        navToggle.addEventListener('click', () => {
            setNav(!body.classList.contains('nav-open'));
        });

        nav.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setNav(false));
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('nav-open')) {
            setNav(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 820 && body.classList.contains('nav-open')) {
            setNav(false);
        }
    });

    /* ----------------------------------------------------------------------
       Kaydırma sırasında üst menü gölgesi
       ------------------------------------------------------------------- */
    const header = document.querySelector('[data-header]');

    const updateHeader = () => {
        if (header) {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        }
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    /* ----------------------------------------------------------------------
       Bildirimleri otomatik kapat
       ------------------------------------------------------------------- */
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        let timer = null;

        const closeToast = () => {
            if (timer) {
                window.clearTimeout(timer);
            }
            toast.classList.add('is-hidden');
            window.setTimeout(() => toast.remove(), 320);
        };

        const closeButton = toast.querySelector('[data-toast-close]');
        if (closeButton) {
            closeButton.addEventListener('click', closeToast);
        }

        timer = window.setTimeout(closeToast, 6000);
    });

    /* ----------------------------------------------------------------------
       Sayaç animasyonu
       ------------------------------------------------------------------- */
    const counters = document.querySelectorAll('[data-counter]');

    const formatCounter = (value, decimals) => {
        if (decimals > 0) {
            return value.toFixed(decimals).replace('.', ',');
        }

        return String(Math.round(value));
    };

    const animateCounter = (element) => {
        if (element.dataset.animated === '1') {
            return;
        }

        element.dataset.animated = '1';

        const rawTarget = String(element.dataset.counter || '0').replace(',', '.');
        const target = Number.parseFloat(rawTarget);
        const decimals = Number.parseInt(element.dataset.decimals || '0', 10);

        if (!Number.isFinite(target)) {
            element.textContent = '0';
            return;
        }

        if (reduceMotion) {
            element.textContent = formatCounter(target, decimals);
            return;
        }

        const duration = 900;
        const startedAt = performance.now();

        const step = (now) => {
            const progress = Math.min((now - startedAt) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = target * eased;

            element.textContent = formatCounter(value, decimals);

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                element.textContent = formatCounter(target, decimals);
            }
        };

        window.requestAnimationFrame(step);
    };

    if (counters.length > 0) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.35 });

            counters.forEach((counter) => observer.observe(counter));
        } else {
            counters.forEach(animateCounter);
        }
    }

    /* ----------------------------------------------------------------------
       Izgara / liste görünümü
       ------------------------------------------------------------------- */
    const movieGrid = document.querySelector('[data-movie-grid]');
    const viewButtons = document.querySelectorAll('[data-view]');
    const storageKey = 'chnstream-view';

    const applyView = (view) => {
        if (movieGrid) {
            movieGrid.classList.toggle('is-list', view === 'list');
        }

        viewButtons.forEach((button) => {
            const active = button.dataset.view === view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });

        try {
            window.localStorage.setItem(storageKey, view);
        } catch (error) {
            /* Gizli sekmede localStorage kapalı olabilir; sorun değil. */
        }
    };

    if (viewButtons.length > 0) {
        let savedView = 'grid';

        try {
            savedView = window.localStorage.getItem(storageKey) || 'grid';
        } catch (error) {
            savedView = 'grid';
        }

        applyView(savedView === 'list' ? 'list' : 'grid');

        viewButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyView(button.dataset.view === 'list' ? 'list' : 'grid');
            });
        });
    }

    /* ----------------------------------------------------------------------
       Filtre formunda seçim değişince otomatik gönder
       ------------------------------------------------------------------- */
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => {
            const form = field.closest('form');

            if (!form) {
                return;
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });

    /* ----------------------------------------------------------------------
       "/" ile aramaya odaklan
       ------------------------------------------------------------------- */
    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const isTyping = target instanceof HTMLElement && (
            target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.tagName === 'SELECT' ||
            target.isContentEditable
        );

        if (event.key === '/' && !isTyping) {
            const searchInput = document.querySelector('[data-search-input]');

            if (searchInput) {
                event.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    /* ----------------------------------------------------------------------
       Metin alanı karakter sayaçları
       ------------------------------------------------------------------- */
    const bindCharacterCounter = (inputSelector, countSelector, limit) => {
        const input = document.querySelector(inputSelector);
        const counter = document.querySelector(countSelector);

        if (!input || !counter) {
            return;
        }

        const update = () => {
            counter.textContent = input.value.length + ' / ' + limit;
        };

        input.addEventListener('input', update);
        update();
    };

    bindCharacterCounter('[data-desc-input]', '[data-desc-count]', 1000);
    bindCharacterCounter('[data-plot-input]', '[data-plot-count]', 2000);
    bindCharacterCounter('[data-comment-input]', '[data-comment-count]', 600);

    /* ----------------------------------------------------------------------
       Afiş yüklenemezse yedek kapağı göster
       ------------------------------------------------------------------- */
    document.querySelectorAll('[data-poster-img]').forEach((image) => {
        image.addEventListener('error', () => {
            image.style.display = 'none';
        });
    });

    /* ----------------------------------------------------------------------
       Form canlı önizlemesi
       ------------------------------------------------------------------- */
    const filmForm = document.querySelector('[data-film-form]');

    if (filmForm) {
        const titleInput = filmForm.querySelector('[name="baslik"]');
        const categoryInput = filmForm.querySelector('[name="kategori"]');
        const directorInput = filmForm.querySelector('[name="yonetmen"]');
        const yearInput = filmForm.querySelector('[name="yil"]');
        const ratingInput = filmForm.querySelector('[data-rating-input]');
        const imdbInput = filmForm.querySelector('[name="imdb"]');
        const posterInput = filmForm.querySelector('[name="poster"]');
        const posterFileInput = filmForm.querySelector('[data-file-input]');
        const posterDrop = filmForm.querySelector('[data-file-drop]');
        const posterFileLabel = filmForm.querySelector('[data-file-label]');
        let posterObjectUrl = null;

        const ratingOutput = document.querySelector('[data-rating-output]');
        const previewPoster = document.querySelector('[data-preview-poster]');
        const previewImage = document.querySelector('[data-preview-img]');
        const previewInitials = document.querySelector('[data-preview-initials]');
        const previewStatus = document.querySelector('[data-preview-status]');
        const previewStatusIcon = document.querySelector('[data-preview-status-icon]');
        const previewStatusText = document.querySelector('[data-preview-status-text]');
        const previewRating = document.querySelector('[data-preview-rating]');
        const previewImdbList = document.querySelectorAll('[data-preview-imdb]');
        const previewCategory = document.querySelector('[data-preview-category]');
        const previewTitle = document.querySelector('[data-preview-title]');
        const previewMeta = document.querySelector('[data-preview-meta]');

        const statusMap = {
            'İzlendi': { className: 'watched', icon: 'fa-circle-check' },
            'İzlenecek': { className: 'watchlist', icon: 'fa-bookmark' },
            'İzleniyor': { className: 'watching', icon: 'fa-circle-play' }
        };

        const formatRating = (value) => {
            const number = Number.parseFloat(value);

            return Number.isFinite(number) ? number.toFixed(1).replace('.', ',') : '0,0';
        };

        const initialsFrom = (text) => {
            const words = text.trim().split(/\s+/).filter(Boolean).slice(0, 2);

            if (words.length === 0) {
                return '?';
            }

            const letters = words.map((word) => word.charAt(0)).join('');

            try {
                return letters.toLocaleUpperCase('tr-TR');
            } catch (error) {
                return letters.toUpperCase();
            }
        };

        const gradientClass = (text) => {
            let hash = 0;
            const source = text || 'Genel';

            for (let index = 0; index < source.length; index += 1) {
                hash = ((hash * 31) + source.charCodeAt(index)) >>> 0;
            }

            return 'poster-grad-' + ((hash % 6) + 1);
        };

        const updatePoster = () => {
            if (!previewImage) {
                return;
            }

            if (posterObjectUrl) {
                URL.revokeObjectURL(posterObjectUrl);
                posterObjectUrl = null;
            }

            const dosya = posterFileInput && posterFileInput.files ? posterFileInput.files[0] : null;

            if (dosya) {
                posterObjectUrl = URL.createObjectURL(dosya);
                previewImage.src = posterObjectUrl;
                previewImage.hidden = false;

                return;
            }

            const url = posterInput ? posterInput.value.trim() : '';

            if (url !== '') {
                previewImage.src = url;
                previewImage.hidden = false;
            } else {
                previewImage.hidden = true;
                previewImage.removeAttribute('src');
            }
        };

        const updatePreview = () => {
            const titleValue = titleInput ? titleInput.value.trim() : '';
            const categoryValue = categoryInput ? categoryInput.value.trim() : '';
            const directorValue = directorInput ? directorInput.value.trim() : '';
            const yearValue = yearInput ? yearInput.value.trim() : '';
            const ratingValue = ratingInput ? ratingInput.value : '8';
            const imdbValue = imdbInput ? imdbInput.value.trim() : '';

            if (previewTitle) {
                previewTitle.textContent = titleValue || 'Film adı burada görünecek';
            }

            if (previewInitials) {
                previewInitials.textContent = initialsFrom(titleValue || 'Yeni Film');
            }

            if (previewCategory) {
                previewCategory.textContent = categoryValue || 'Kategori';
            }

            if (previewMeta) {
                previewMeta.textContent = (directorValue || 'Yönetmen') + ' · ' + (yearValue || 'Yıl');
            }

            if (previewPoster) {
                for (let index = 1; index <= 6; index += 1) {
                    previewPoster.classList.remove('poster-grad-' + index);
                }

                previewPoster.classList.add(gradientClass(categoryValue || titleValue));
            }

            if (ratingOutput) {
                ratingOutput.textContent = formatRating(ratingValue);
            }

            if (previewRating) {
                previewRating.textContent = formatRating(ratingValue);
            }

            previewImdbList.forEach((element) => {
                element.textContent = formatRating(imdbValue || '0');
            });

            const selectedStatus = filmForm.querySelector('input[name="durum"]:checked');

            if (selectedStatus) {
                const status = statusMap[selectedStatus.value] || statusMap['İzlenecek'];

                if (previewStatus) {
                    previewStatus.className = 'status-badge status-' + status.className;
                }

                if (previewStatusIcon) {
                    previewStatusIcon.className = 'fa-solid ' + status.icon;
                }

                if (previewStatusText) {
                    previewStatusText.textContent = selectedStatus.value;
                }
            }

            updatePoster();
        };

        [titleInput, categoryInput, directorInput, yearInput, imdbInput, posterInput].forEach((field) => {
            if (field) {
                field.addEventListener('input', updatePreview);
            }
        });

        if (ratingInput) {
            ratingInput.addEventListener('input', updatePreview);
        }

        filmForm.querySelectorAll('input[name="durum"]').forEach((radio) => {
            radio.addEventListener('change', updatePreview);
        });

        if (posterFileInput) {
            posterFileInput.addEventListener('change', () => {
                const dosya = posterFileInput.files ? posterFileInput.files[0] : null;

                if (posterFileLabel) {
                    posterFileLabel.textContent = dosya
                        ? dosya.name
                        : 'JPG, PNG, WEBP veya GIF · en fazla 2 MB';
                }

                if (posterDrop) {
                    posterDrop.classList.toggle('has-file', Boolean(dosya));
                }

                updatePreview();
            });
        }

        if (previewImage) {
            previewImage.addEventListener('error', () => {
                previewImage.hidden = true;
            });
        }

        updatePreview();

        /* Form gönderilirken butonu kilitle ve geri bildirim ver. */
        filmForm.addEventListener('submit', () => {
            const submitButton = filmForm.querySelector('button[type="submit"]');

            if (submitButton && !submitButton.disabled) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Kaydediliyor...';
            }
        });
    }

    /* ----------------------------------------------------------------------
       Yıldızlı puanlama
       ------------------------------------------------------------------- */
    document.querySelectorAll('[data-star-rating]').forEach((rating) => {
        const labels = Array.from(rating.querySelectorAll('.star-rating-item'));
        const form = rating.closest('form');
        const output = form ? form.querySelector('[data-rating-label]') : null;

        if (labels.length === 0) {
            return;
        }

        const selectedValue = () => {
            const checked = rating.querySelector('input:checked');
            return checked ? Number.parseInt(checked.value, 10) : 0;
        };

        const paint = (value) => {
            labels.forEach((label) => {
                const labelValue = Number.parseInt(label.dataset.value || '0', 10);
                label.classList.toggle('is-filled', labelValue <= value);
            });
        };

        const writeOutput = (value) => {
            if (output && Number.isFinite(value) && value > 0) {
                output.textContent = value.toFixed(1).replace('.', ',');
            }
        };

        labels.forEach((label) => {
            const value = Number.parseInt(label.dataset.value || '0', 10);

            label.addEventListener('mouseenter', () => paint(value));
            label.addEventListener('focusin', () => paint(value));

            label.addEventListener('click', () => {
                paint(value);
                writeOutput(value);
            });
        });

        rating.addEventListener('mouseleave', () => paint(selectedValue()));

        rating.addEventListener('change', () => {
            const value = selectedValue();
            paint(value);
            writeOutput(value);
        });

        paint(selectedValue());
    });

    /* ----------------------------------------------------------------------
       Silme onay penceresi
       ------------------------------------------------------------------- */
    const deleteModal = document.querySelector('[data-delete-modal]');

    if (deleteModal) {
        const deleteName = deleteModal.querySelector('[data-delete-name]');
        const confirmButton = deleteModal.querySelector('[data-delete-confirm]');
        const closeButtons = deleteModal.querySelectorAll('[data-modal-close]');

        let pendingForm = null;
        let lastFocused = null;

        const openModal = (form) => {
            pendingForm = form;
            lastFocused = document.activeElement;

            if (deleteName) {
                const filmTitle = form.dataset.filmTitle || 'Bu kayıt';
                deleteName.textContent = '“' + filmTitle + '”';
            }

            deleteModal.hidden = false;
            body.classList.add('modal-open');

            if (confirmButton) {
                confirmButton.focus();
            }
        };

        const closeModal = () => {
            deleteModal.hidden = true;
            body.classList.remove('modal-open');
            pendingForm = null;

            if (lastFocused && typeof lastFocused.focus === 'function') {
                lastFocused.focus();
            }
        };

        document.querySelectorAll('.js-delete-form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === '1') {
                    return;
                }

                event.preventDefault();
                openModal(form);
            });
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                if (!pendingForm) {
                    return;
                }

                pendingForm.dataset.confirmed = '1';
                pendingForm.submit();
            });
        }

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        deleteModal.addEventListener('click', (event) => {
            if (event.target === deleteModal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (deleteModal.hidden) {
                return;
            }

            if (event.key === 'Escape') {
                closeModal();
                return;
            }

            if (event.key === 'Tab') {
                const focusable = Array.from(deleteModal.querySelectorAll(
                    'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                ));

                if (focusable.length === 0) {
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
    }

    /* ----------------------------------------------------------------------
       Kaydırma ilerlemesi ve başa dön düğmesi
       ------------------------------------------------------------------- */
    const progressBar = document.querySelector('[data-scroll-progress]');
    const backToTop = document.querySelector('[data-back-to-top]');

    const updateScrollUI = () => {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = maxScroll > 0 ? Math.min(scrollTop / maxScroll, 1) : 0;

        if (progressBar) {
            progressBar.style.width = (ratio * 100).toFixed(2) + '%';
        }

        if (backToTop) {
            backToTop.hidden = scrollTop < 420;
        }
    };

    updateScrollUI();
    window.addEventListener('scroll', updateScrollUI, { passive: true });
    window.addEventListener('resize', updateScrollUI);

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }

    /* ----------------------------------------------------------------------
       Komut paleti (Ctrl / Cmd + K)
       ------------------------------------------------------------------- */
    const commandPalette = document.querySelector('[data-command-palette]');
    const commandOpeners = document.querySelectorAll('[data-command-open]');
    const commandInput = document.querySelector('[data-command-input]');
    const commandResults = document.querySelector('[data-command-results]');
    const commandItems = Array.from(document.querySelectorAll('[data-command-item]'));
    const commandEmpty = document.querySelector('[data-command-empty]');
    const commandCloseTargets = document.querySelectorAll('[data-command-close]');

    if (commandPalette && commandInput) {
        let commandLastFocused = null;

        const visibleItems = () => commandItems.filter((item) => !item.hidden);

        const selectItem = (item) => {
            commandItems.forEach((candidate) => candidate.classList.toggle('is-selected', candidate === item));
        };

        const selectedItem = () => commandResults
            ? commandResults.querySelector('[data-command-item].is-selected')
            : null;

        const updateGroups = () => {
            if (!commandResults) {
                return;
            }

            const labels = Array.from(commandResults.querySelectorAll('.command-group-label'));

            labels.forEach((label) => {
                let node = label.nextElementSibling;
                let hasVisible = false;

                while (node && !node.classList.contains('command-group-label')) {
                    if (node.matches('[data-command-item]') && !node.hidden) {
                        hasVisible = true;
                        break;
                    }
                    node = node.nextElementSibling;
                }

                label.hidden = !hasVisible;
            });
        };

        const filterItems = () => {
            const query = commandInput.value.trim().toLocaleLowerCase('tr-TR');

            commandItems.forEach((item) => {
                const text = (item.dataset.commandText || item.textContent || '').toLocaleLowerCase('tr-TR');
                item.hidden = query !== '' && !text.includes(query);
            });

            const visible = visibleItems();
            commandItems.forEach((item) => item.classList.remove('is-selected'));

            if (visible.length > 0) {
                selectItem(visible[0]);
            }

            if (commandEmpty) {
                commandEmpty.hidden = visible.length > 0;
            }

            updateGroups();
        };

        const openCommand = () => {
            commandLastFocused = document.activeElement;
            commandPalette.hidden = false;
            body.classList.add('modal-open');
            commandInput.value = '';
            filterItems();

            window.setTimeout(() => commandInput.focus(), 30);
        };

        const closeCommand = () => {
            commandPalette.hidden = true;
            body.classList.remove('modal-open');
            commandItems.forEach((item) => item.classList.remove('is-selected'));

            if (commandLastFocused && typeof commandLastFocused.focus === 'function') {
                commandLastFocused.focus();
            }
        };

        commandOpeners.forEach((opener) => {
            opener.addEventListener('click', (event) => {
                event.preventDefault();
                openCommand();
            });
        });

        commandCloseTargets.forEach((target) => {
            target.addEventListener('click', closeCommand);
        });

        commandInput.addEventListener('input', filterItems);

        commandInput.addEventListener('keydown', (event) => {
            const visible = visibleItems();

            if (event.key === 'Escape') {
                event.preventDefault();
                closeCommand();
                return;
            }

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();

                if (visible.length === 0) {
                    return;
                }

                const current = selectedItem();
                const currentIndex = current ? visible.indexOf(current) : -1;
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                let nextIndex = (currentIndex + direction + visible.length) % visible.length;

                if (currentIndex === -1) {
                    nextIndex = 0;
                }

                const next = visible[nextIndex];
                selectItem(next);
                next.scrollIntoView({ block: 'nearest' });
                return;
            }

            if (event.key === 'Enter') {
                event.preventDefault();

                const current = selectedItem() || visible[0];

                if (current) {
                    current.click();
                }
            }
        });

        commandItems.forEach((item) => {
            item.addEventListener('mouseenter', () => selectItem(item));

            if (item.hasAttribute('data-command-theme')) {
                item.addEventListener('click', () => {
                    if (themeToggle) {
                        themeToggle.click();
                    }
                    closeCommand();
                });
            } else {
                item.addEventListener('click', () => closeCommand());
            }
        });

        document.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();

                if (commandPalette.hidden) {
                    openCommand();
                } else {
                    closeCommand();
                }
            }

            if (event.key === 'Escape' && !commandPalette.hidden) {
                closeCommand();
            }
        });

        filterItems();
    }

    /* ----------------------------------------------------------------------
       Giriş ekranı: şifreyi göster / demo bilgilerini doldur
       ------------------------------------------------------------------- */
    const passwordInput = document.querySelector('[data-password-input]');
    const passwordToggle = document.querySelector('[data-password-toggle]');

    if (passwordInput && passwordToggle) {
        const passwordIcon = passwordToggle.querySelector('i');

        passwordToggle.addEventListener('click', () => {
            const gorunur = passwordInput.getAttribute('type') === 'text';

            passwordInput.setAttribute('type', gorunur ? 'password' : 'text');
            passwordToggle.setAttribute('aria-pressed', gorunur ? 'false' : 'true');
            passwordToggle.setAttribute('aria-label', gorunur ? 'Şifreyi göster' : 'Şifreyi gizle');
            passwordToggle.setAttribute('title', gorunur ? 'Şifreyi göster' : 'Şifreyi gizle');

            if (passwordIcon) {
                passwordIcon.classList.toggle('fa-eye', gorunur);
                passwordIcon.classList.toggle('fa-eye-slash', !gorunur);
            }

            passwordInput.focus();
        });
    }

    const demoFill = document.querySelector('[data-demo-fill]');

    if (demoFill) {
        demoFill.addEventListener('click', () => {
            const kullaniciInput = document.getElementById('kullanici_adi');
            const sifreInput = document.getElementById('sifre');

            if (kullaniciInput) {
                kullaniciInput.value = 'admin';
            }

            if (sifreInput) {
                sifreInput.value = 'sinema123';
            }

            demoFill.classList.add('is-done');
            demoFill.innerHTML = '<i class="fa-solid fa-check"></i> Bilgiler dolduruldu';

            window.setTimeout(() => {
                demoFill.classList.remove('is-done');
                demoFill.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Bilgileri doldur';
            }, 2200);

            const submit = document.querySelector('.auth-submit');

            if (submit) {
                submit.focus();
            }
        });
    }
})();
