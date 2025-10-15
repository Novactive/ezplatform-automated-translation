import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';

import SelectedLocationComponent from "./selected.location.component";

const SelectedLocationsComponent = (props) => {
    const { items, onDelete } = props;
    const [locations, setLocations] = useState([]);
    const handleClick = function (locationId) {
         setLocations(() =>  {
             const results = locations.filter(location => parseInt(location.id) !== parseInt(locationId));
             onDelete(results);
             return results;
         });

    };
    useEffect(() => {
        setLocations((prevState) => [...prevState, ...items]);
    }, [items]);

    return (
        <ul className="location-tree">
            {locations.map((location) => (
                <SelectedLocationComponent location={location} onDelete={handleClick} />
            ))}
        </ul>
    );
};

SelectedLocationsComponent.propTypes = {
    items: PropTypes.array,
    onDelete: PropTypes.func,
};

SelectedLocationsComponent.defaultProps = {
    items: [],
};

export default SelectedLocationsComponent;
