export const portfolioAsset = (name) => {
    if (/^https?:\/\//i.test(name) || name.startsWith('/images/portfolio/')) {
        return name;
    }

    return `/images/portfolio/${name}`;
};
