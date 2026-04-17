const LOGO_SRC = '/images/logo-brasa.png';

export default function AppLogo() {
    return (
        <>
            <img
                src={LOGO_SRC}
                alt=""
                className="size-8 shrink-0 object-contain"
                width={32}
                height={32}
            />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold tracking-wide">
                    Brasa
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    Pantanal
                </span>
            </div>
        </>
    );
}
