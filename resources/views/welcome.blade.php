<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Clínica Cabanillas</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0f766e;
            --primary-dark: #0b5e59;
            --secondary: #0891b2;
            --text: #17252a;
            --text-light: #6b7b83;
            --white: #ffffff;
        }

        body {
            min-height: 100vh;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;

            background:
                radial-gradient(circle at 10% 20%, rgba(8, 145, 178, 0.14), transparent 25%),
                radial-gradient(circle at 90% 80%, rgba(15, 118, 110, 0.14), transparent 30%),
                #f7fbfc;

            color: var(--text);

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        /* Fondo decorativo */

        .circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(1px);
            pointer-events: none;
        }

        .circle-1 {
            width: 380px;
            height: 380px;
            background: rgba(15, 118, 110, 0.07);
            top: -130px;
            right: -100px;
        }

        .circle-2 {
            width: 290px;
            height: 290px;
            background: rgba(8, 145, 178, 0.08);
            bottom: -120px;
            left: -70px;
        }

        .container {
            width: 100%;
            max-width: 1180px;
            padding: 40px;

            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            align-items: center;
            gap: 70px;

            position: relative;
            z-index: 2;
        }

        /* =========================
           IZQUIERDA
        ========================= */

        .content {
            max-width: 620px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 45px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;

            border-radius: 15px;

            background: linear-gradient(
                135deg,
                var(--primary),
                var(--secondary)
            );

            display: flex;
            align-items: center;
            justify-content: center;

            box-shadow: 0 10px 30px rgba(15, 118, 110, 0.22);
        }

        .brand-icon svg {
            width: 29px;
            height: 29px;
            stroke: white;
        }

        .brand-name strong {
            display: block;

            font-size: 17px;
            font-weight: 750;
        }

        .brand-name span {
            display: block;

            margin-top: 3px;

            font-size: 11px;
            color: var(--text-light);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            background: #e7f8f6;
            color: var(--primary-dark);

            border: 1px solid #c5ece8;

            padding: 7px 12px;

            border-radius: 50px;

            font-size: 11px;
            font-weight: 650;

            margin-bottom: 20px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;

            background: #15a071;
            border-radius: 50%;
        }

        h1 {
            font-size: clamp(42px, 5vw, 66px);
            line-height: 1.04;

            letter-spacing: -2.5px;

            margin-bottom: 22px;

            font-weight: 780;
        }

        h1 span {
            background: linear-gradient(
                120deg,
                var(--primary),
                var(--secondary)
            );

            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .description {
            max-width: 540px;

            font-size: 15px;
            line-height: 1.75;

            color: var(--text-light);

            margin-bottom: 34px;
        }

        .buttons {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-primary {
            min-width: 195px;
            height: 52px;

            padding: 0 24px;

            border: none;
            border-radius: 12px;

            background: linear-gradient(
                135deg,
                var(--primary),
                #0b8582
            );

            color: white;

            font-size: 13px;
            font-weight: 700;

            text-decoration: none;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;

            cursor: pointer;

            transition: 0.25s;

            box-shadow: 0 12px 30px rgba(15, 118, 110, 0.23);
        }

        .btn-primary:hover {
            transform: translateY(-2px);

            box-shadow: 0 16px 35px rgba(15, 118, 110, 0.30);
        }

        .btn-primary svg {
            width: 17px;
            height: 17px;

            transition: 0.2s;
        }

        .btn-primary:hover svg {
            transform: translateX(3px);
        }

        .secure {
            margin-top: 25px;

            display: flex;
            align-items: center;
            gap: 7px;

            color: #8a989f;

            font-size: 10px;
        }

        .secure svg {
            width: 14px;
            height: 14px;
        }


        /* =========================
           DERECHA
        ========================= */

        .visual {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            width: 390px;
            height: 470px;

            border-radius: 32px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.96),
                    rgba(241,250,250,0.93)
                );

            border: 1px solid rgba(255, 255, 255, 0.9);

            box-shadow:
                0 30px 80px rgba(26, 86, 91, 0.14),
                inset 0 1px 0 rgba(255,255,255,0.9);

            position: relative;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card::before {
            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            border-radius: 50%;

            background: linear-gradient(
                135deg,
                rgba(15,118,110,.08),
                rgba(8,145,178,.13)
            );
        }

        .tooth-container {
            width: 205px;
            height: 205px;

            border-radius: 60px;

            background: linear-gradient(
                145deg,
                #ffffff,
                #ecf9f8
            );

            box-shadow:
                0 22px 50px rgba(15,118,110,.15),
                inset 0 0 0 1px rgba(15,118,110,.05);

            display: flex;
            align-items: center;
            justify-content: center;

            z-index: 2;
        }

        .tooth-container svg {
            width: 112px;
            height: 112px;

            stroke: var(--primary);
        }

        .floating {
            position: absolute;

            background: rgba(255,255,255,.95);

            border: 1px solid #edf1f2;
            border-radius: 13px;

            box-shadow: 0 12px 30px rgba(0,0,0,.07);

            padding: 10px 14px;

            font-size: 10px;
            color: var(--text-light);

            z-index: 5;
        }

        .floating strong {
            display: block;

            color: var(--text);

            font-size: 12px;

            margin-bottom: 2px;
        }

        .floating-1 {
            left: -30px;
            top: 75px;
        }

        .floating-2 {
            right: -25px;
            bottom: 85px;
        }

        .status {
            position: absolute;

            top: 20px;
            right: 20px;

            display: flex;
            align-items: center;
            gap: 6px;

            font-size: 9px;
            color: #54816d;

            background: #effbf4;

            border-radius: 30px;

            padding: 6px 10px;
        }

        .status-dot {
            width: 6px;
            height: 6px;

            border-radius: 50%;

            background: #20aa6b;
        }

        footer {
            position: absolute;

            bottom: 18px;
            left: 0;
            right: 0;

            text-align: center;

            font-size: 9px;
            color: #9aa6ab;
        }


        /* RESPONSIVE */

        @media (max-width: 900px) {

            body {
                overflow-y: auto;
            }

            .container {
                grid-template-columns: 1fr;

                text-align: center;

                gap: 45px;

                padding: 35px 25px 80px;
            }

            .content {
                margin: auto;
            }

            .brand {
                justify-content: center;
            }

            .description {
                margin-left: auto;
                margin-right: auto;
            }

            .buttons {
                justify-content: center;
            }

            .secure {
                justify-content: center;
            }

            .visual {
                display: none;
            }

            footer {
                position: fixed;
            }
        }

        @media (max-width: 500px) {

            .container {
                padding-top: 25px;
            }

            .brand {
                margin-bottom: 35px;
            }

            h1 {
                letter-spacing: -1.5px;
            }

            .description {
                font-size: 13px;
            }

            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>


    <main class="container">

        <!-- =====================
             CONTENIDO
        ====================== -->

        <section class="content">

            <div class="brand">

                <div class="brand-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="
                            M12 3
                            C9.5 1.8 6.7 2 5.1 4.1
                            C3.5 6.1 4.1 9.1 5.2 11.7
                            C6.2 14 6.2 17.8 7.5 20
                            C8.2 21.2 9.4 21 10 19.7
                            L11.1 16.5
                            C11.4 15.6 12.6 15.6 12.9 16.5
                            L14 19.7
                            C14.6 21 15.8 21.2 16.5 20
                            C17.8 17.8 17.8 14 18.8 11.7
                            C19.9 9.1 20.5 6.1 18.9 4.1
                            C17.3 2 14.5 1.8 12 3Z
                        "/>
                    </svg>

                </div>

                <div class="brand-name">
                    <strong>Clínica Cabanillas</strong>
                    <span>Sistema de gestión odontológica</span>
                </div>

            </div>


            <div class="badge">
                <div class="badge-dot"></div>
                Plataforma de gestión clínica
            </div>


            <h1>
                Bienvenido a
                <span>Clínica Cabanillas</span>
            </h1>


            <p class="description">
                Gestiona pacientes, citas, tratamientos y operaciones
                de la clínica desde una plataforma moderna,
                organizada y segura.
            </p>


            <div class="buttons">

                <a href="/login" class="btn-primary">

                    Ingresar al sistema

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>

                </a>

            </div>


            <div class="secure">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <rect x="4" y="10" width="16" height="10" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>

                Acceso exclusivo para personal autorizado

            </div>

        </section>


        <!-- =====================
             VISUAL
        ====================== -->

        <section class="visual">

            <div class="card">

                <div class="status">
                    <div class="status-dot"></div>
                    Sistema disponible
                </div>


                <div class="floating floating-1">
                    <strong>Gestión sencilla</strong>
                    Pacientes y citas
                </div>


                <div class="tooth-container">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="1.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="
                            M12 3
                            C9.5 1.8 6.7 2 5.1 4.1
                            C3.5 6.1 4.1 9.1 5.2 11.7
                            C6.2 14 6.2 17.8 7.5 20
                            C8.2 21.2 9.4 21 10 19.7
                            L11.1 16.5
                            C11.4 15.6 12.6 15.6 12.9 16.5
                            L14 19.7
                            C14.6 21 15.8 21.2 16.5 20
                            C17.8 17.8 17.8 14 18.8 11.7
                            C19.9 9.1 20.5 6.1 18.9 4.1
                            C17.3 2 14.5 1.8 12 3Z
                        "/>
                    </svg>

                </div>


                <div class="floating floating-2">
                    <strong>Todo organizado</strong>
                    En un solo sistema
                </div>

            </div>

        </section>

    </main>


    <footer>
        © 2026 Clínica Cabanillas · Sistema de Gestión Odontológica
    </footer>

</body>
</html>